<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 14.11.16
 * Time: 16:21
 */

namespace RP\SearchBundle\Controller;

use Common\Core\Constants\RequestConstant;
use Common\Core\Controller\ApiController;
use Common\Core\Exceptions\SearchServiceException;
use RP\SearchBundle\Services\Mapping\CitySearchMapping;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SearchCityController extends ApiController
{

    /**
     * Ключ контекста объекта ответа клиенту
     *
     * @const string KEY_FIELD_RESPONSE
     */
    const KEY_FIELD_RESPONSE = 'cities';

    /**
     * Поиск города по названию
     * применяется во вногих местах где нам необходимо получить город
     * надо в старом API спроксировать запрос
     *
     * @param Request $request Объект запроса
     * @return Response
     */
    public function searchCityByNameAction(Request $request)
    {
        try {
            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::NULLED_PARAMS);

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

            // получаем фильтры и парсим их в нужный вид для дальнейшей работы
            $countryName = trim(mb_substr($request->get(RequestConstant::FILTER_COUNTRY), 0, 128));
            $types = $this->getParseFilters($request->get(RequestConstant::FILTER_TYPES));

            /** @var string ID пользователя */
            $userId = $this->getRequestUserId();

            $citySearchService = $this->getCitySearchService();

            // старый запрос без сортировки по популярности
            // $cities = $citySearchService->searchCityByName($userId, $searchText, $this->getGeoPoint(), $this->getSkip(), $this->getCount());

            // новый запрос с сортировкой по популярности
            $cities = $citySearchService->searchTopCityByName(
                $userId,
                $searchText,
                $countryName,
                $types,
                $this->getSkip(),
                $this->getCount()
            );

            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {
                $oldFormat = $citySearchService->cityTransformer->transform($cities, CitySearchMapping::CONTEXT);

                return $this->_handleViewWithData(
                    [self::KEY_FIELD_RESPONSE => $oldFormat],
                    null,
                    !self::INCLUDE_IN_CONTEXT
                );
            }

            $result = array_merge(
                ['info' => $citySearchService->getTotalHits()],
                $cities ?: []
            );

            return $this->_handleViewWithData($result);

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Получить город по его ID
     *
     * @param Request $request Объект запроса
     * @param string $cityId
     * @return Response
     */
    public function searchCityByIdAction(Request $request, $cityId)
    {
        $data = null;

        try {
            $citySearchService = $this->getCitySearchService();
            $city = $citySearchService->searchRecordById(
                CitySearchMapping::CONTEXT,
                CitySearchMapping::ID_FIELD,
                $cityId
            );

            $data = $city;
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }

        return $this->_handleViewWithData($data);
    }

    /**
     * Получить список порции городов
     * которые самые популярные
     *
     * @param Request $request Объект запроса
     * @return Response
     */
    public function getCitiesListAction(Request $request)
    {
        $data = [];

        try {
            $citySearchService = $this->getCitySearchService();
            $cities = $citySearchService->getTopCitiesList(
                $this->getRequestUserId(),
                $this->getGeoPoint(),
                $this->getSkip(),
                $this->getCount()
            );

            $citiesData = $citySearchService->getAggregations();

            if (!empty($citiesData)) {
                foreach ($citiesData as &$city) {
                    $city['city'] = $citySearchService->searchRecordById(
                        CitySearchMapping::CONTEXT,
                        CitySearchMapping::ID_FIELD,
                        $city['key']
                    );
                }
            }

            $data = $citiesData;
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }

        return $this->_handleViewWithData($data);
    }
}