<?php
/**
 * Контроллер поиска маркеров
 * по нескольким типам (например: people,places)
 */
namespace RP\SearchBundle\Controller;

use Common\Core\Controller\ApiController;
use Common\Core\Facade\Service\User\UserProfileService;
use Elastica\Exception\ElasticsearchException;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
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
        if( !$this->getGeoPoint()->isValid() )
        {
            return $this->_handleViewWithError(new BadRequestHttpException('Incorrect geoPoint requests data'));
        }

        // получаем фильтры и парсим их в нужный вид для дальнейшей работы
        $types = $this->getParseFilters($filterTypes);

        /** @var Текст запроса */
        $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);

        $userId = $this->getRequestUserId();

        // получаем сервис многотипного поиска
        $markersSearchService = $this->getCommonSearchService();

        // выполняем поиск по маркерам
        $markers = $markersSearchService->searchMarkersByTypes(
            $userId,
            $types,
            $this->getGeoPoint(),
            $searchText
        );

        return $this->_handleViewWithData($markers);
    }
}