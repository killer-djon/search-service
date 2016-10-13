<?php
/**
 * Основной контроллер поиска по местам
 */
namespace RP\SearchBundle\Controller;

use Common\Core\Controller\ApiController;
use Symfony\Component\HttpFoundation\Request;
use Common\Core\Constants\RequestConstant;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SearchPlacesController extends ApiController
{
    /**
     * Параметр поиска при запросе по id места
     *
     * @const string PLACE_ID_SEARCH_PARAM
     */
    const PLACE_ID_SEARCH_PARAM = 'placeId';

    public function searchPlacesByNameAction(Request $request)
    {
        /** @var Текст запроса */
        $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);
        if (is_null($searchText)) {
            return $this->_handleViewWithError(
                new BadRequestHttpException(
                    'Не указана поисковая строка searchText',
                    null,
                    Response::HTTP_BAD_REQUEST
                )
            );
        }

        if (mb_strlen($searchText) <= 2) {
            return $this->_handleViewWithError(
                new BadRequestHttpException(
                    'Поисковая строка должны быть больше двух символов',
                    null,
                    Response::HTTP_BAD_REQUEST
                )
            );
        }

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

        $placeSearchService = $this->getPlacesSearchService();
        $places = $placeSearchService->searchPlacesByName($userId, $searchText, $this->getGeoPoint(), $this->getSkip(), $this->getCount());

        return $this->_handleViewWithData([
            'info'      => [
                'places' => $placeSearchService->getTotalHits(),
            ],
            'paginator' => [
                'places' => $placeSearchService->getPaginationAdapter($this->getSkip(), $this->getCount()),
            ],
            'places'    => $places,
        ]);
    }
}