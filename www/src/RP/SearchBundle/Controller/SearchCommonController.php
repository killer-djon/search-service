<?php
/**
 * Класс общего поиска по всем коллекциям
 */

namespace RP\SearchBundle\Controller;

use Common\Core\Controller\ApiController;
use Common\Core\Exceptions\SearchServiceException;
use Common\Core\Facade\Service\User\UserProfileService;
use Elastica\Exception\ElasticsearchException;
use RP\SearchBundle\Services\Mapping\EventsSearchMapping;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
use RP\SearchBundle\Services\Mapping\PostSearchMapping;
use RP\SearchBundle\Services\Mapping\TagNameSearchMapping;
use Symfony\Component\HttpFoundation\Request;
use Common\Core\Constants\RequestConstant;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SearchCommonController extends ApiController
{

    /**
     * Кол-во выводимых интересов по умолчанию
     *
     * @const int DEFAULT_INTERESTS_COUNT
     */
    const DEFAULT_INTERESTS_COUNT = 5;

    /**
     * Метод осуществляющий глобальный поиск
     * в контексте всех имеющихся типов в базе еластика
     *
     * @param Request $request Объект запроса
     * @param string|null $filterType Категория запроса (people,places,discounts...)
     * @return Response Возвращаем ответ
     */
    public function searchCommonByFilterAction(Request $request, $filterType)
    {
        try {
            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::NULLED_PARAMS);
            $filterType = ($filterType == '_all' ? RequestConstant::NULLED_PARAMS : $this->getParseFilters($filterType));

            // получаем из запроса ID пользователя
            $userId = $this->getRequestUserId();

            // получаем ID города если он указан в запросе
            $cityId = $request->get(RequestConstant::CITY_SEARCH_PARAM, RequestConstant::NULLED_PARAMS);
            $cityId = !empty($cityId) ? $cityId : RequestConstant::NULLED_PARAMS;

            $commonSearchService = $this->getCommonSearchService();
            // ужасный костыль после перехода к новому сервису надо убрать
            if (!is_null($version) && (int)$version == RequestConstant::DEFAULT_VERSION) {
                $commonSearchService->setOldFormat(true);
            }

            /** @var Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM);
            $searchText = !empty($searchText) ? $searchText : RequestConstant::NULLED_PARAMS;

            if (is_null($cityId) && is_null($searchText)) {
                /*return $this->_handleViewWithError(new BadRequestHttpException(
                    'Необходимо указать один из обязательных параметров запроса (cityId или searchText)'
                ), Response::HTTP_BAD_REQUEST);*/
                return $this->_handleViewWithData([]);
            }

            if (!is_null($searchText) && mb_strlen($searchText) < 3) {
                return $this->_handleViewWithData([]);
            }

            $isFlat = $this->getBoolRequestParam($request->get(RequestConstant::IS_FLAT_PARAM, false));

            if ($isFlat === true) {
                if (is_null($filterType) || empty($filterType)) {
                    return $this->_handleViewWithError(
                        new BadRequestHttpException(
                            'Необходимо указать параметры фильтров',
                            null,
                            Response::HTTP_BAD_REQUEST
                        )
                    );
                }
                $searchData = $commonSearchService->commonFlatSearchByFilters(
                    $userId,
                    $filterType,
                    $searchText,
                    $cityId,
                    $this->getGeoPoint(),
                    $this->getSkip(),
                    $this->getCount()
                );

                return $this->_handleViewWithData(array_merge(
                    ['info' => $commonSearchService->getTotalHits()],
                    [
                        'pagination' => $commonSearchService->getPaginationAdapter(
                            $this->getSkip(),
                            $this->getCount(),
                            (isset($searchData['info']) ? $searchData['info']['totalHits'] : null)
                        ),
                    ],
                    ['items' => ($searchData ? $this->revertToScalarTagsMatchFields($searchData) : [])]
                ));
            }

            $point = $this->getGeoPoint();

            $searchData = $commonSearchService->commonSearchByFilters(
                $userId,
                $searchText,
                $cityId,
                $point->isValid() && !$point->isEmpty() ? $point : null,
                $filterType,
                $this->getSkip(),
                $this->getCount()
            );

            foreach ($searchData['items'] as $key => &$searchItemInfo) {

                if (!empty($searchItemInfo)) {
                    if ($key == EventsSearchMapping::CONTEXT) {
                        $this->extractWillComeFriends($searchItemInfo, $userId);
                    }
                    $item[$key] = $searchItemInfo;
                    $searchData['pagination'][$key] = $commonSearchService->getPaginationAdapter(
                        $this->getSkip(),
                        $this->getCount(),
                        (isset($searchData['info']) ? $searchData['info']['searchType'][$key]['totalHits'] : null)
                    );
                }
            }

            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {

                $oldFormat = $this->getVersioningData($commonSearchService);

                $data = [];
                if (!empty($oldFormat)) {
                    $keys = array_keys($oldFormat['results']);

                    foreach ($keys as $format) {

                        $data[$format] = $commonSearchService->peopleTransformer->transform(
                            $oldFormat['results'],
                            $format,
                            'item'
                        );
                    }

                    $data = [
                        'results'    => (!empty($data) ? $data : new \stdClass),
                        'info'       => $oldFormat['info'],
                        'pagination' => isset($searchData['pagination']) ? $searchData['pagination'] : null,
                    ];
                }

                return $this->_handleViewWithData(
                    $data,
                    null,
                    !self::INCLUDE_IN_CONTEXT
                );
            }

            $result = $this->getNewFormatResponse(
                $commonSearchService
            );



            /** Временный костыль, убираем HTML из текста для мобильных платформ */
            $headerUserAgent = $request->headers->get('platform', $request->headers->get('User-Agent'));

            if (!empty($result) && isset($result['items'][PostSearchMapping::CONTEXT]) && preg_match('/(ios|android)/i', $headerUserAgent)) {
                array_walk($result['items'][PostSearchMapping::CONTEXT], function (&$item) {
                    $item['message'] = (!empty($item['message']) ? strip_tags($item['message']) : '');

                });
            }

            $this->revertToScalarTagsMatchFields($result);

            /** Отправляем запрос на АПИ для сохранения истории поиска */
            $env = $this->container->get('kernel')->getEnvironment();
            $apiUrl = $env !== 'prod' ? $this->container->getParameter('serviceApiDev') : $this->container->getParameter('serviceApiProd');
            $apiClient = new \GuzzleHttp\Client();
            $searchHistoryRequest = new \GuzzleHttp\Psr7\Request('POST', $apiUrl, [
                'tokenId' => $request->headers->get('tokenId'),
                'Content-Type' => 'application/json'
            ], \GuzzleHttp\json_encode([
                'cityId' => $cityId,
                'searchText' => $searchText
            ]));

            $apiClient->sendAsync($searchHistoryRequest);

            return $this->_handleViewWithData($result);

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Вывод ТОП n интересов
     *
     * @param Request $request Объект запроса
     * @return Response Возвращаем ответ
     */
    public function searchInterestsAction(Request $request)
    {
        try {
            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::NEW_DEFAULT_VERSION);
            // получаем из запроса ID пользователя
            $userId = $this->getRequestUserId();

            $commonSearchService = $this->getCommonSearchService();
            $interests = $commonSearchService->searchCountInterests($userId, 0, self::DEFAULT_INTERESTS_COUNT);

            $resultInterests = $commonSearchService->tagNamesTransformer->transform($interests, TagNameSearchMapping::CONTEXT);

            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {
                return $this->_handleViewWithData(
                    $resultInterests,
                    null,
                    !self::INCLUDE_IN_CONTEXT
                );
            }

            return $this->_handleViewWithData([
                TagNameSearchMapping::CONTEXT => $resultInterests,
            ]);

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Поиск интересов
     *
     * @param Request $request Объект запроса
     * @return Response Возвращаем ответ
     */
    public function searchInterestsByNameAction(Request $request)
    {
        try {
            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::NULLED_PARAMS);
            // получаем из запроса ID пользователя
            $userId = $this->getRequestUserId();

            /** @var Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM);

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

            $commonSearchService = $this->getCommonSearchService();
            $interests = $commonSearchService->searchInterestsByName($userId, $searchText, $this->getSkip(), $this->getCount());

            $resultInterests = $commonSearchService->tagNamesTransformer->transform($interests, TagNameSearchMapping::CONTEXT);

            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {
                return $this->_handleViewWithData(
                    $resultInterests,
                    null,
                    !self::INCLUDE_IN_CONTEXT
                );
            }

            return $this->_handleViewWithData([
                TagNameSearchMapping::CONTEXT => $resultInterests,
            ]);
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Поиск с автодополнением
     *
     * @param Request $request
     * @param string $searchText
     * @return Response Возвращаем ответ
     */
    public function searchSuggestAction(Request $request, $searchText)
    {
        try{
            $commonSearchService = $this->getCommonSearchService();
            $suggests = $commonSearchService->suggestSearch($searchText, $this->getSkip(), $this->getCount());

            if( is_null($suggests) || empty($suggests) )
            {
                return $this->_handleViewWithData([]);
            }

            $info = $commonSearchService->getTotalHits();

            return $this->_handleViewWithData([
                'info' => $info,
                'pagination' => $commonSearchService->getPaginationAdapter(
                    $this->getSkip(),
                    $this->getCount()
                ),
                'items' => $suggests,

            ]);
        }catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }
}