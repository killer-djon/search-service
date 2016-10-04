<?php
/**
 * Основной контроллер поиска в еластике
 */
namespace RP\SearchBundle\Controller;

use Common\Core\Controller\ApiController;
use Elastica\Exception\ElasticsearchException;
use Symfony\Component\HttpFoundation\Request;
use Common\Core\Constants\RequestConstant;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SearchController extends ApiController
{
    /**
     * Запрос для поиска пользователя
     */
    public function searchUsersAction(Request $request)
    {
        /** @var Текст запроса */
        $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);
        $skip = $request->get(RequestConstant::SEARCH_SKIP_PARAM, RequestConstant::DEFAULT_SEARCH_SKIP);
        $count = $request->get(RequestConstant::SEARCH_LIMIT_PARAM, RequestConstant::DEFAULT_SEARCH_LIMIT);

        if (is_null($searchText)) {
            return $this->_handleViewWithError(
                new BadRequestHttpException(
                    'You must set searchText param to request',
                    null,
                    Response::HTTP_BAD_REQUEST
                )
            );
        }

        try
        {
            $peopleSearchService = $this->getPeopleSearchService();
            $resultPeople = $peopleSearchService->searchPeopleByFilter($skip, $count);

            return $this->_handleViewWithData([
                'people' => $resultPeople
            ]);

        }catch(ElasticsearchException $e)
        {
            return $this->_handleViewWithError(
                new ElasticsearchException($e->getMessage(), $e->getCode())
            );
        }
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
