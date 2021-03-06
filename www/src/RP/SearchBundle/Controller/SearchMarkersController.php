<?php
/**
 * Контроллер поиска маркеров
 * по нескольким типам (например: people,places)
 */

namespace RP\SearchBundle\Controller;

use Common\Core\Constants\Location;
use Common\Core\Controller\ApiController;
use Common\Core\Exceptions\SearchServiceException;
use Common\Core\Facade\Service\Geo\GeoPointService;
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

            // Определяем выводить ли нам класстерные данные
            $isCluster = $this->getBoolRequestParam($request->get(RequestConstant::IS_CLUSTER_PARAM), false);

            $geoHashCell = null;
            if ($isCluster === false) {
                // получаем порцию данных из ячайки класстера
                $geoHashCell = $request->get(Location::GEO_HASH_CELL_PARAM);
                $geoHashCell = (!is_null($geoHashCell) && !empty($geoHashCell) ? $geoHashCell : null);
            }

            /** @var int Теперь версия по умолчанию везде будет 4, в начале июЛя выпилим костыли для третьей версии */
            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::NEW_DEFAULT_VERSION);

            // получаем фильтры и парсим их в нужный вид для дальнейшей работы
            $types = $this->getParseFilters($filterTypes);

            $userId = $this->getRequestUserId();

            /** @var Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM);
            $searchText = !empty($searchText) ? $searchText : RequestConstant::NULLED_PARAMS;

            // получаем сервис многотипного поиска
            $markersSearchService = $this->getCommonSearchService();

            if ($isCluster) {
                // указываем что класстеры должны быть сгруппированны
                $markersSearchService->setClusterGrouped();
            }

            $point = $this->getGeoPoint();
            if (is_null($point->getRadius())) {
                $point->setRadius(GeoPointService::DEFAULT_MARKERS_MAP_RADIUS_M);
            }

            // выполняем поиск по маркерам
            $markers = $markersSearchService->searchMarkersByFilters(
                $userId,
                $types,
                $point,
                $searchText,
                $isCluster,
                $geoHashCell,
                $this->getSkip(),
                ($isCluster ? 1 : ($request->get(RequestConstant::SEARCH_LIMIT_PARAM, RequestConstant::DEFAULT_SEARCH_UNLIMIT)))
            );

            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {
                $oldFormat = $this->getVersioningData($markersSearchService);

                if (!empty($oldFormat)) {
                    $keys = array_keys($oldFormat['results']);
                    $oldFormat['results'] = AbstractTransformer::array_filter_recursive($oldFormat['results']);

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

            return $this->_handleViewWithData($this->revertToScalarTagsMatchFields($markers));

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }
}