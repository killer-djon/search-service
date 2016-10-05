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

    const USERNAME_SEARCH_PARAM = 'username';

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

        $skip = $request->get(RequestConstant::SEARCH_SKIP_PARAM, RequestConstant::DEFAULT_SEARCH_SKIP);
        $count = $request->get(RequestConstant::SEARCH_LIMIT_PARAM, RequestConstant::DEFAULT_SEARCH_LIMIT);

        if (is_null($searchText)) {
            return $this->_handleViewWithError(
                new BadRequestHttpException(
                    'Не указана поисковая строка username',
                    null,
                    Response::HTTP_BAD_REQUEST
                )
            );
        }

        try {
            $peopleSearchService = $this->getPeopleSearchService();
            $peopleSearchService->searchPeopleByUserName($userId, $searchText, $skip, $count);

            return $this->_handleViewWithData([
                'info' => [
                    'people'    => $peopleSearchService->getTotalHits()
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
