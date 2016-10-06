<?php
/**
 * Основной контроллер поиска в еластике
 */
namespace RP\SearchBundle\Controller;

use Common\Core\Controller\ApiController;
use Common\Core\Controller\ControllerTrait;
use Elastica\Exception\ElasticsearchException;
use Symfony\Component\HttpFoundation\Request;
use Common\Core\Constants\RequestConstant;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SearchController extends ApiController
{
    use ControllerTrait;

    /**
     * Параметр поиска при запросе по городу
     * @const string CITY_SEARCH_PARAM
     */
    const CITY_SEARCH_PARAM = 'cityId';

    /**
     * Поиск пользователей по имени/фамилии
     * искомая строка содержиться либо в начале либо в середине
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchUsersByNameAction(Request $request)
    {
        /** @var Текст запроса */
        $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);

        /** @var ID пользователя */
        $userId = $request->get(RequestConstant::USER_ID_PARAM, RequestConstant::NULLED_PARAMS);

        if (is_null($userId)) {
            return $this->_handleViewWithError(
                new BadRequestHttpException(
                    'Не указан userId пользователя',
                    null,
                    Response::HTTP_BAD_REQUEST
                )
            );
        }

        if (is_null($searchText)) {
            return $this->_handleViewWithError(
                new BadRequestHttpException(
                    'Не указана поисковая строка searchText',
                    null,
                    Response::HTTP_BAD_REQUEST
                )
            );
        }

        try {
            $peopleSearchService = $this->getPeopleSearchService();
            $peopleSearchService->searchPeopleByName($userId, $searchText, $this->getGeoPoint(), $this->getSkip(), $this->getCount());

            return $this->_handleViewWithData([
                'info' => [
                    'people'    => $peopleSearchService->getTotalHits()
                ],
                'cluster' => [
                    'people'    => $peopleSearchService->getAggregations()
                ],
                'results' => [
                    'people'    => $peopleSearchService->getTotalResults()
                ],
            ]);

        } catch (ElasticsearchException $e) {
            return $this->_handleViewWithError(
                new ElasticsearchException($e->getMessage(), $e->getCode())
            );
        }
    }


    public function searchUsersByCityAction(Request $request)
    {
        $cityId = $request->get(self::CITY_SEARCH_PARAM, RequestConstant::NULLED_PARAMS);

        if( is_null($cityId) )
        {
            return $this->_handleViewWithError(
                new BadRequestHttpException(
                    'Не задан город для поиска пользователей',
                    null,
                    Response::HTTP_BAD_REQUEST
                )
            );
        }

        $peopleSearchService = $this->getPeopleSearchService();
        $peopleSearchService->searchPeopleByCityId($cityId, $this->getGeoPoint(), $this->getSkip(), $this->getCount());

        return $this->_handleViewWithData([
            'info' => [
                'people'    => $peopleSearchService->getTotalHits()
            ],
            'cluster' => [
                'people'    => $peopleSearchService->getAggregations()
            ],
            'results' => [
                'people'    => $peopleSearchService->getTotalResults()
            ],
        ]);
    }


    /**
     * Получаем сервис поиска пользователей (людей)
     * через еластик
     *
     * @return \RP\SearchBundle\Services\PeopleSearchService
     */
    public function getPeopleSearchService()
    {
        return $this->get('rp_search.search_service.people');
    }
}
