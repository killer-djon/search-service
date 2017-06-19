<?php

namespace RP\SearchBundle\Controller;

use Common\Core\Constants\RequestConstant;
use Common\Core\Controller\ApiController;
use Common\Core\Exceptions\SearchServiceException;
use RP\SearchBundle\Services\Mapping\CountrySearchMapping;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class SearchCountryController
 * @package RP\SearchBundle\Controller
 */
class SearchCountryController extends ApiController
{

    /**
     * Ключ контекста объекта ответа клиенту
     *
     * @const string KEY_FIELD_RESPONSE
     */
    const KEY_FIELD_RESPONSE = 'countries';

    /**
     * Поиск страны по названию
     *
     * @param Request $request Объект запроса
     * @return Response
     */
    public function searchCountryByNameAction(Request $request)
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

            $countrySearchService = $this->getCountrySearchService();

            $countries = $countrySearchService->searchCountryByName(
                $searchText,
                $this->getSkip(),
                $this->getCount()
            );

            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {
                $oldFormat = $countrySearchService->countryTransformer->transform($countries, CountrySearchMapping::CONTEXT);

                return $this->_handleViewWithData(
                    [self::KEY_FIELD_RESPONSE => $oldFormat],
                    null,
                    !self::INCLUDE_IN_CONTEXT
                );
            }

            $result = array_merge(
                ['info' => $countrySearchService->getTotalHits()],
                $countries ?: []
            );

            return $this->_handleViewWithData($result);

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Получить страну по её ID
     *
     * @param Request $request Объект запроса
     * @param string $countryId
     * @return Response
     */
    public function searchCountryByIdAction(Request $request, $countryId)
    {
        $data = null;

        try {
            $countrySearchService = $this->getCountrySearchService();
            $country = $countrySearchService->searchRecordById(
                CountrySearchMapping::CONTEXT,
                CountrySearchMapping::ID_FIELD,
                $countryId
            );

            $data = $country;
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }

        return $this->_handleViewWithData($data);
    }
}
