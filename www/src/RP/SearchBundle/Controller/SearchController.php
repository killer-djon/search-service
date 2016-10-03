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
            $searchService = $this->getSearchService();
            $mustQuery = $showlQuery = $mustNotQuery = $filterQuery = [];


            $filterQuery[] = $searchService->getFilterCondition()->getExistsFilter('name');
            $filterQuery[] = $searchService->getFilterCondition()->getTermsFilter('visible', ['all']);

            $searchQuery = $searchService->getQueryCondition()->getBoolQuery($mustQuery, $showlQuery, $mustNotQuery);
            $result = $searchService->searchDocuments('place', $searchQuery, [
                'filters' => $filterQuery,
                'sortings' => [
                    'id' => [
                        'order' => 'asc'
                    ]
                ]
            ]);

            return $this->_handleViewWithData([
                'places' => $result
            ]);
        }catch(ElasticsearchException $e)
        {
            throw new ElasticsearchException($e);
        }
    }
}
