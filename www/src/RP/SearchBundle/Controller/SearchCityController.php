<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 14.11.16
 * Time: 16:21
 */
namespace RP\SearchBundle\Controller;

use Common\Core\Controller\ApiController;
use Common\Core\Exceptions\SearchServiceException;
use Common\Core\Facade\Service\User\UserProfileService;
use Elastica\Exception\ElasticsearchException;
use RP\SearchBundle\Services\CitySearchService;
use RP\SearchBundle\Services\Mapping\CitySearchMapping;
use Symfony\Component\HttpFoundation\Request;
use Common\Core\Constants\RequestConstant;
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
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @return \Symfony\Component\HttpFoundation\Response
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

            /** @var ID пользователя */
            $userId = $this->getRequestUserId();

            $citySearchService = $this->getCitySearchService();
            $cities = $citySearchService->searchCityByName($userId, $searchText, $this->getGeoPoint(), $this->getSkip(), $this->getCount());

            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {
                $oldFormat = $this->getVersioningData($citySearchService);

                return $this->_handleViewWithData(
                    (isset($oldFormat['results'][CitySearchMapping::CONTEXT])
                    ? $oldFormat['results'][CitySearchMapping::CONTEXT]
                    : []),
                    null,
                    !self::INCLUDE_IN_CONTEXT
                );
            }

            return $this->_handleViewWithData(array_merge(
                    [
                        'info' => $citySearchService->getTotalHits(),
                    ],
                    $cities ?: [])
            );


        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Получить город по его ID
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @param string $cityId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchCityByIdAction(Request $request, $cityId)
    {
        $peopleSearchService = $this->getPeopleSearchService();

        try {
            $userContext = $peopleSearchService->searchRecordById(PeopleSearchMapping::CONTEXT, PeopleSearchMapping::AUTOCOMPLETE_ID_PARAM, $userId);

            if (!is_null($userContext)) {
                return $this->_handleViewWithData($userContext);
            }

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }

        return null;
    }
}