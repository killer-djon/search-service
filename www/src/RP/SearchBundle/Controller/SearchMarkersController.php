<?php
/**
 * Контроллер поиска маркеров
 * по нескольким типам (например: people,places)
 */
namespace RP\SearchBundle\Controller;

use Common\Core\Constants\Location;
use Common\Core\Controller\ApiController;
use Common\Core\Exceptions\SearchServiceException;
use Common\Core\Facade\Service\User\UserProfileService;
use Elastica\Exception\ElasticsearchException;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
use RP\SearchBundle\Services\Transformers\AbstractTransformer;
use Symfony\Component\HttpFoundation\Request;
use Common\Core\Constants\RequestConstant;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SearchMarkersController extends ApiController
{

    /**
     * Метод осуществляющий поиск маркеров
     * по заданным условиям локации (радиус, широта и долгота)
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @param mixed $filterTypes Набор переданных фильтров
     * @return \Symfony\Component\HttpFoundation\Response Возвращаем ответ
     */
    public function searchMarkersByFilterAction(Request $request, $filterTypes)
    {
        try {

            if (!$this->getGeoPoint()->isValid()) {
                return $this->_handleViewWithError(new BadRequestHttpException('Некорректные координаты геопозиции'), Response::HTTP_BAD_REQUEST);
            }

            // получаем порцию данных из ячайки класстера
            $geoHashCell = $request->get(Location::GEO_HASH_CELL_PARAM);
            $geoHashCell = (!is_null($geoHashCell) && !empty($geoHashCell) ? $geoHashCell : null);

            if (mb_strlen($geoHashCell) <= 0) {
                if (is_null($this->getGeoPoint()->getRadius())) {
                    return $this->_handleViewWithError(new BadRequestHttpException('Радиус должен быть установлен'), Response::HTTP_BAD_REQUEST);
                }
            }

            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::NULLED_PARAMS);
            // получаем фильтры и парсим их в нужный вид для дальнейшей работы
            $types = $this->getParseFilters($filterTypes);

            $userId = $this->getRequestUserId();

            // Определяем выводить ли нам класстерные данные
            $isCluster = $request->get(RequestConstant::IS_CLUSTER_PARAM, false);

            // получаем сервис многотипного поиска
            $markersSearchService = $this->getCommonSearchService();

            if ($isCluster) {
                // указываем что класстеры должны быть сгруппированны
                $markersSearchService->setClusterGrouped();
            }

            // выполняем поиск по маркерам
            $markers = $markersSearchService->searchMarkersByFilters(
                $userId,
                $types,
                $this->getGeoPoint(),
                $isCluster,
                $geoHashCell,
                $this->getSkip(),
                $request->get(RequestConstant::SEARCH_LIMIT_PARAM, RequestConstant::DEFAULT_SEARCH_UNLIMIT)
            );


            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {
                $oldFormat = $this->getVersioningData($markersSearchService);

                if (!empty($oldFormat)) {
                    $keys = array_keys($oldFormat['results']);

                    foreach ($keys as $format) {
                        foreach ($oldFormat['results'][$format] as &$obj) {
                            AbstractTransformer::recursiveTransformAvatar($obj);
                        }
                    }

                    //$oldFormat['results'] = AbstractTransformer::array_filter_recursive($oldFormat['results']);

                }

                if ($isCluster && isset($markers['cluster'])) {
                    $oldFormat['cluster'] = $markers['cluster'];
                    unset($oldFormat['results']);
                }

                return $this->_handleViewWithData(
                    $oldFormat,
                    null,
                    !self::INCLUDE_IN_CONTEXT
                );
            }

            return $this->_handleViewWithData($markers);

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }
}