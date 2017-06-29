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
            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::NEW_DEFAULT_VERSION);

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

            $citySearchService = $this->getCitySearchService();

            // старый запрос без сортировки по популярности
            // $cities = $citySearchService->searchCityByName($userId, $searchText, $this->getGeoPoint(), $this->getSkip(), $this->getCount());
            if (!empty($countryName)) {
                $cities = $citySearchService->searchCityByName($searchText, $countryName, $types, $this->getSkip(), $this->getCount());
                $result = array_merge(
                    ['info' => $citySearchService->getTotalHits()],
                    $cities ?: []
                );

                return $this->_handleViewWithData($result);
            }

            // новый запрос с сортировкой по популярности
            $cities = $citySearchService->searchTopCityByName(
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
        try {
            $result = [];

            $skip = $this->getSkip();
            $count = $this->getCount();

            $userId = $this->getRequestUserId();

            $citySearchService = $this->getCitySearchService();

            $searchedCities = $citySearchService->getLastSearchedCitiesList(
                $userId,
                $skip,
                $count
            );

            if (!empty($searchedCities)) {
                foreach ($searchedCities as $city) {
                    $result[] = [
                        'key'       => $city['id'],
                        'doc_count' => false, // этот параметр нужен для совместимости с данными из агрегации топ мест
                        'city'      => $citySearchService->searchRecordById(
                            CitySearchMapping::CONTEXT,
                            CitySearchMapping::ID_FIELD,
                            $city['id']
                        ),
                    ];

                    if (count($result) >= $count) {
                        break;
                    }
                }
            }

            $alreadyInResult = array_column($result, 'key');

            if (count($result) < $count) {
                $citySearchService->getTopCitiesList(
                    $alreadyInResult,
                    $skip,
                    $count
                );

                $topCities = $citySearchService->getAggregations();

                if (!empty($topCities)) {
                    foreach ($topCities as $city) {
                        $city['city'] = $citySearchService->searchRecordById(
                            CitySearchMapping::CONTEXT,
                            CitySearchMapping::ID_FIELD,
                            $city['key']
                        );

                        $result[] = $city;

                        if (count($result) >= $count) {
                            break;
                        }
                    }
                }
            }
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }

        return $this->_handleViewWithData($result);
    }
}