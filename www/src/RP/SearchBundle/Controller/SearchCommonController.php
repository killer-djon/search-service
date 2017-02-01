<?php
/**
 * Класс общего поиска по всем коллекциям
 */
namespace RP\SearchBundle\Controller;

use Common\Core\Controller\ApiController;
use Common\Core\Exceptions\SearchServiceException;
use Common\Core\Facade\Service\User\UserProfileService;
use Elastica\Exception\ElasticsearchException;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
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
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @param string|null $filterType Категория запроса (people,places,discounts...)
     * @return \Symfony\Component\HttpFoundation\Response Возвращаем ответ
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

            if( !is_null($searchText) && mb_strlen($searchText) < 3 )
            {
                return $this->_handleViewWithData([]);
            }

            $searchData = $commonSearchService->commonSearchByFilters(
                $userId,
                $searchText,
                $cityId,
                $this->getGeoPoint(),
                $filterType,
                $this->getSkip(),
                $this->getCount()
            );

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
                        'results' => $data,
                        'info'    => $oldFormat['info'],
                    ];
                }

                return $this->_handleViewWithData(
                    $data,
                    null,
                    !self::INCLUDE_IN_CONTEXT
                );
            }

            return $this->_handleViewWithData(array_merge(
                [
                    'info' => $commonSearchService->getTotalHits(),
                ],
                $searchData ? $this->revertToScalarTagsMatchFields($searchData) : []
            ));

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Вывод ТОП n интересов
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @return \Symfony\Component\HttpFoundation\Response Возвращаем ответ
     */
    public function searchInterestsAction(Request $request)
    {
        try {
            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::NULLED_PARAMS);
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
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @return \Symfony\Component\HttpFoundation\Response Возвращаем ответ
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
}