<?php
/**
 * Основной контроллер поиска по местам
 */

namespace RP\SearchBundle\Controller;

use Common\Core\Constants\RequestConstant;
use Common\Core\Controller\ApiController;
use Common\Core\Exceptions\SearchServiceException;
use Common\Util\ArrayHelper;
use RP\SearchBundle\Services\Mapping\DiscountsSearchMapping;
use RP\SearchBundle\Services\Mapping\PlaceSearchMapping;
use RP\SearchBundle\Services\Mapping\PlaceTypeSearchMapping;
use RP\SearchBundle\Services\Transformers\AbstractTransformer;
use Symfony\Component\HttpFoundation\Request;
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
     * Ключ контекста объекта ответа клиенту
     *
     * @const string KEY_FIELD_RESPONSE
     */
    const KEY_FIELD_RESPONSE = 'places';

    /**
     * Ключ контекста объекта ответа клиенту
     *
     * @const string KEY_FIELD_PLACE_TYPE_RESPONSE
     */
    const KEY_FIELD_PLACE_TYPE_RESPONSE = 'placetype';

    /**
     * Поиск мест по введенному названию (части названия)
     * поиск идет по всем местам
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchPlacesByNameAction(Request $request)
    {
        try {
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
            $userId = $this->getRequestUserId();

            $placeSearchService = $this->getPlacesSearchService();
            $places = $placeSearchService->searchPlacesByName($userId, $searchText, $this->getGeoPoint(),
                $this->getSkip(), $this->getCount());

            return $this->returnDataResult($placeSearchService, self::KEY_FIELD_RESPONSE);
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
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
        try {
            /** @var ID пользователя */
            $userId = $this->getRequestUserId();

            /** @var ID города */
            $cityId = $this->getRequestCityId();

            /** @var Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);

            $placeSearchService = $this->getPlacesSearchService();
            $places = $placeSearchService->searchPlacesByCity($userId, $cityId, $this->getGeoPoint(), $searchText,
                $this->getSkip(), $this->getCount());

            return $this->returnDataResult($placeSearchService, self::KEY_FIELD_RESPONSE);
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }

    }

    public function searchPlacesByDiscountAction(Request $request)
    {
        try {
            /** @var string ID пользователя */
            $userId = $this->getRequestUserId();

            $params = [
                'search' => $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS),
                'cityId' => $request->get(RequestConstant::CITY_SEARCH_PARAM, RequestConstant::NULLED_PARAMS),
            ];

            $placeSearchService = $this->getPlacesSearchService();
            $places = $placeSearchService->searchPlacesByDiscount($userId, $this->getGeoPoint(), $params,
                $this->getSkip(), $this->getCount());

            return $this->returnDataResult($placeSearchService, self::KEY_FIELD_RESPONSE);
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Поиск скидочных мест под старый формат api (для rp-front)
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchPlacesByPromoAction(Request $request)
    {
        try {
            $userId = $this->getRequestUserId();
            $skip = (int)$this->getSkip();
            $count = (int)$this->getCount();
            $point = $this->getGeoPoint();
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);

            /**
             * Если для skip/count можно указать явно тип данных
             * то для countryId и cityId НЕТ таких стран/городов как 0
             *
             * так как (int)NULL = 0
             */
            $countryId = $request->get(RequestConstant::COUNTRY_SEARCH_PARAM, RequestConstant::NULLED_PARAMS);
            $cityId = $request->get(RequestConstant::CITY_SEARCH_PARAM, RequestConstant::NULLED_PARAMS);

            /**
             * Этот хак нужен для нового формата данных
             * так как текущий формат уже устарел
             *
             * По умолчанию старая версия для совместимости 3
             */
            $version = (int)$request->get(RequestConstant::VERSION_PARAM, RequestConstant::DEFAULT_VERSION);

            $placeSearchService = $this->getPlacesSearchService();

            $result = $placeSearchService->searchPromoPlaces($userId, $point, $searchText, $skip, $count, $countryId, $cityId);

            if( $version == RequestConstant::NEW_DEFAULT_VERSION )
            {
                $placeSearchService->initTotalResults(
                    $result['list']
                );

                return $this->_handleViewWithData(
                    $this->getNewFormatResponse(
                        $placeSearchService,
                        null,
                        $result['meta']
                    )
                );
            }

            return $this->_handleViewWithData($result);
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
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
            /** @var ID пользователя */
            $userId = $this->getRequestUserId();
            $place = $placeSearchService->getPlaceById(
                $userId, PlaceSearchMapping::CONTEXT,
                PlaceSearchMapping::PLACE_ID_FIELD,
                $placeId,
                $this->getGeoPoint()
            );

            $place = $this->revertToScalarTagsMatchFields($place);

            return $this->_handleViewWithData($place);

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Поиск мест по заданному ID
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $discountId id места скидки
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchDiscountByIdAction(Request $request, $discountId)
    {
        $placeSearchService = $this->getPlacesSearchService();

        try {
            /** @var ID пользователя */
            $userId = $this->getRequestUserId();
            $place = $placeSearchService->getDiscountById($userId, PlaceSearchMapping::CONTEXT,
                PlaceSearchMapping::PLACE_ID_FIELD, $discountId);

            return $this->_handleViewWithData($place);

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Поиск типов мест по названию
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchPlaceTypeByNameAction(Request $request)
    {
        try {
            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::NEW_DEFAULT_VERSION);

            /** @var bool Получение параметра типа ответа данных, либо дерево либо flat массив */
            $isFlat = $this->getBoolRequestParam($request->get(RequestConstant::IS_FLAT_PARAM), false);

            /** @var ID пользователя */
            $userId = $this->getRequestUserId();

            /** @var string Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM);
            $searchText = empty($searchText) ? RequestConstant::NULLED_PARAMS : $searchText;

            $placeSearchService = $this->getPlacesSearchService();

            $placesType = $placeSearchService->searchPlacesTypeByName(
                $userId,
                $searchText
            );

            if (empty($placesType)) {
                return $this->_handleViewWithData([]);
            }

            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {
                $oldFormat = $this->getVersioningData($placeSearchService);

                $oldFormat = $placeSearchService->placeTypeTransformer->transform($oldFormat['results'],
                    PlaceTypeSearchMapping::CONTEXT);

                return $this->_handleViewWithData(
                    [
                        'data' => [
                            'result' => $oldFormat
                        ]
                    ],
                    null,
                    !self::INCLUDE_IN_CONTEXT
                );
            }


            if (!$isFlat) {
                $tree = AbstractTransformer::convertToTree($placesType[PlaceTypeSearchMapping::CONTEXT]);
                $placeSearchService->initTotalResults([PlaceTypeSearchMapping::CONTEXT => $tree]);
            }

            return $this->_handleViewWithData(
                $this->getNewFormatResponse(
                    $placeSearchService,
                    PlaceTypeSearchMapping::CONTEXT
                )
            );

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Поиск типов мест по названию
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $placeTypeId ID типа места
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchPlaceTypeByIdAction(Request $request, $placeTypeId)
    {
        $placeSearchService = $this->getPlacesSearchService();

        try {
            /** @var ID пользователя */
            $userId = $this->getRequestUserId();
            $placeType = $placeSearchService->getPlaceTypeById(
                $userId,
                PlaceTypeSearchMapping::CONTEXT,
                PlaceTypeSearchMapping::PLACE_TYPE_ID_FIELD,
                $placeTypeId
            );

            return $this->_handleViewWithData($placeType);

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }
}
