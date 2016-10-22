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
    
    /**
     * Параметр поиска при запросе по городу
     *
     * @const string CITY_SEARCH_PARAM
     */
    const CITY_SEARCH_PARAM = 'cityId';

	/**
	 * Поиск мест по введенному названию (части названия)
	 * поиск идет по всем местам 
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
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
    
    
    /**
	 * Поиск мест по указанному городу
	 * т.е. выводим те места которые в городе расположены
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param string $cityId ID города поиска
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
    public function searchPlacesByCityAction(Request $request, $cityId)
    {
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
        
        /** @var ID города */
        $cityId = $request->get(self::CITY_SEARCH_PARAM, RequestConstant::NULLED_PARAMS);

        if (is_null($cityId)) {
            return $this->_handleViewWithError(
                new BadRequestHttpException(
                    'Не задан город для поиска пользователей',
                    null,
                    Response::HTTP_BAD_REQUEST
                )
            );
        }
        
        $placeSearchService = $this->getPlacesSearchService();
        $places = $placeSearchService->searchPlacesByCity($userId, $cityId, $this->getGeoPoint(), $this->getSkip(), $this->getCount());
        
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
        
    public function searchPlacesByDiscountAction(Request $request)
    {
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
        
        /** @var Текст запроса */
        $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);

        $placeSearchService = $this->getPlacesSearchService();
        $places = $placeSearchService->searchPlacesByDiscount($userId, $this->getGeoPoint(), $searchText, $this->getSkip(), $this->getCount());
        
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
    
    /**
	 * Поиск мест по заданному ID
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param string $placeId id заданного места
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function searchPlacesByIdAction(Request $request, $placeId)
	{
		$placeSearchService = $this->getPlacesSearchService();

        try {
            $place = $placeSearchService->getPlaceById($placeId);

            return $this->_handleViewWithData($place);

        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
	}
}