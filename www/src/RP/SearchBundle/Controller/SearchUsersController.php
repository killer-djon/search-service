<?php
/**
 * Основной контроллер поиска по людям
 */
namespace RP\SearchBundle\Controller;

use Common\Core\Controller\ApiController;
use Common\Core\Exceptions\SearchServiceException;
use Common\Core\Facade\Service\User\UserProfileService;
use Elastica\Exception\ElasticsearchException;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
use Symfony\Component\HttpFoundation\Request;
use Common\Core\Constants\RequestConstant;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SearchUsersController extends ApiController
{

    /**
     * Ключ контекста объекта ответа клиенту
     *
     * @const string KEY_FIELD_RESPONSE
     */
    const KEY_FIELD_RESPONSE = 'people';

    /**
     * Поиск пользователей по имени/фамилии
     * искомая строка содержиться либо в начале либо в середине
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchUsersByNameAction(Request $request)
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

            /** получаем сервис поиска */
            $peopleSearchService = $this->getPeopleSearchService();
            /** создаем запрос для поиска по имени/фамилии пользователей */
            $people = $peopleSearchService->searchPeopleByName($userId, $searchText, $this->getGeoPoint(), $this->getSkip(), $this->getCount());

            /** выводим результат */
            return $this->returnDataResult($peopleSearchService, self::KEY_FIELD_RESPONSE);
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Поиск пользователей по городу
     * выводим всех найденных пользователей в указанном городе
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchUsersByCityAction(Request $request)
    {
        try {
            /** @var Текст запроса (в случае если ищем по имени) */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);

            /** @var ID пользователя */
            $userId = $this->getRequestUserId();

            /** @var ID города */
            $cityId = $this->getRequestCityId();

            $peopleSearchService = $this->getPeopleSearchService();
            $people = $peopleSearchService->searchPeopleByCityId($userId, $cityId, $this->getGeoPoint(), $searchText, $this->getSkip(), $this->getCount());

            /** выводим результат */
            return $this->returnDataResult($peopleSearchService, self::KEY_FIELD_RESPONSE);
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Поиск пользователей в друзъях
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchUsersByFriendAction(Request $request)
    {
        try {
            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::NULLED_PARAMS);
            $simpleList = $request->get('simpleList');

            /** @var Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);

            /** @var ID пользователя */
            $userId = $this->getRequestUserId();

            $peopleSearchService = $this->getPeopleSearchService();
            $people = $peopleSearchService->searchPeopleFriends($userId, $this->getGeoPoint(), $searchText, $this->getSkip(), $this->getCount());

            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {
                $oldFormat = $this->getVersioningData($peopleSearchService);

                $dataResult = (isset($oldFormat['results'][PeopleSearchMapping::CONTEXT])
                    ? $oldFormat['results'][PeopleSearchMapping::CONTEXT]
                    : []);

                if ($simpleList) {
                    return $this->_handleViewWithData(
                        $dataResult,
                        null,
                        !self::INCLUDE_IN_CONTEXT
                    );
                } else {
                    return $this->_handleViewWithData(
                        [
                            'users'      => (
                            isset($oldFormat['results'][PeopleSearchMapping::CONTEXT]) ?
                                $oldFormat['results'][PeopleSearchMapping::CONTEXT] :
                                []
                            ),
                            'pagination' => $peopleSearchService->getPaginationAdapter($this->getSkip(), $this->getCount()),
                        ],
                        null,
                        !self::INCLUDE_IN_CONTEXT
                    );
                }
            }

            /** выводим результат */
            return $this->returnDataResult($peopleSearchService, self::KEY_FIELD_RESPONSE);
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Поиск пользователей которые могут помочь
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchUsersHelpOffersAction(Request $request)
    {
        try {
            /** @var Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);

            /** @var ID пользователя */
            $userId = $this->getRequestUserId();

            $peopleSearchService = $this->getPeopleSearchService();
            $peopleHelpOffers = $peopleSearchService->searchPeopleHelpOffers(
                $userId,
                $this->getGeoPoint(),
                $searchText,
                $this->getSkip(),
                $this->getCount()
            );

            /** выводим результат */
            return $this->returnDataResult($peopleSearchService, self::KEY_FIELD_RESPONSE);
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Поиск пользователя по его ID
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @param string $userId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchUsersByIdAction(Request $request, $userId)
    {
        $peopleSearchService = $this->getPeopleSearchService();

        try {

            $userContext = $peopleSearchService->searchRecordById(
                PeopleSearchMapping::CONTEXT,
                PeopleSearchMapping::AUTOCOMPLETE_ID_PARAM,
                $userId
            );

            if (!is_null($userContext)) {
                return $this->_handleViewWithData($userContext);
            }

            return null;

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Поиск возможных друзей
     * по совпадению по интересам
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @param string $userId ID пользователя
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchPossibleFriendsAction(Request $request, $userId)
    {
        $peopleSearchService = $this->getPeopleSearchService();

        try {
            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::NULLED_PARAMS);

            /** @var Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);

            $peopleSearchService->setFilterQuery([
                $peopleSearchService->_queryFilterFactory->getNotFilter(
                    $peopleSearchService->_queryFilterFactory->getTermsFilter(PeopleSearchMapping::FRIEND_LIST_FIELD, [
                        $userId,
                    ])
                ),
            ]);

            $possibleFriends = $peopleSearchService->searchPossibleFriendsForUser(
                $userId,
                $this->getGeoPoint(),
                $searchText,
                $this->getSkip(),
                $this->getCount()
            );

            $data = [];

            foreach ($possibleFriends as $keyCategory => $possibleFriend) {
                if (isset($possibleFriend[PeopleSearchMapping::CONTEXT])) {
                    $data = $data + $possibleFriend[PeopleSearchMapping::CONTEXT];
                }
            }

            $action = $request->get('action');

            if (!is_null($action) && $action == 'registration_tour') {
                $peopleSearchService->clearQueryFactory();
                // исторический костыль из приложения
                // чтобы на первом месте в массиве был RP_USER
                $rpUser = $peopleSearchService->searchRecordById(
                    PeopleSearchMapping::CONTEXT,
                    PeopleSearchMapping::AUTOCOMPLETE_ID_PARAM,
                    PeopleSearchMapping::RP_USER_ID
                );

                array_unshift($data, $rpUser);
                array_pop($data);
            }

            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {

                $this->restructTagsField($data);
                $this->restructLocationField($data);
                $data = $this->changeKeysName($data);
                $data = $this->excludeEmptyValue($data);

                return $this->_handleViewWithData(
                    $data,
                    null,
                    !self::INCLUDE_IN_CONTEXT
                );
            }

            return $this->_handleViewWithData([
                'info'                       => $peopleSearchService->getTotalHits(),
                PeopleSearchMapping::CONTEXT => $data,
            ]);

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

}
