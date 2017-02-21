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
use RP\SearchBundle\Services\Transformers\AbstractTransformer;
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
            /** @var ID пользователя */
            $userId = $this->getRequestUserId();

            /** @var ID профиля которого хотим посмотреть */
            $targetUserId = $request->get(RequestConstant::TARGET_USER_ID_PARAM, $userId);

            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::DEFAULT_VERSION);
            $simpleList = $request->get('simpleList');

            /** @var Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);

            $filtersType = $request->get(RequestConstant::FILTERS_PARAM);

            if (is_null($filtersType) || empty($filtersType)) {
                return $this->_handleViewWithError(
                    new BadRequestHttpException(
                        'Не указаны фильтры',
                        null,
                        Response::HTTP_BAD_REQUEST
                    )
                );
            }

            /** @var array Набор фильтров запроса */
            $filters = $this->getParseFilters($filtersType);
            if( $version == RequestConstant::DEFAULT_VERSION )
            {
                $filters = [ 'friends' ];
            }

            $peopleSearchService = $this->getPeopleSearchService();

            /**
             * После того как мы уберем костыли
             * по совместимости со старыми приложениям
             */
            $people = $peopleSearchService->searchPeopleFriends(
                $userId,
                $targetUserId,
                $filters,
                $this->getGeoPoint(),
                $searchText,
                $this->getSkip(),
                $this->getCount()
            );

            if( empty($peopleSearchService->getTotalResults()))
            {
                return $this->_handleViewWithData([]);
            }

            if( $version == RequestConstant::DEFAULT_VERSION )
            {
                $items = (isset($people['items']) && !empty($people['items']) ? $people['items'] : NULL);

                if( is_null($items) )
                {
                    return $this->_handleViewWithData([]);
                }

                $info = $people['info']['searchType'];

                $this->restructTagsField($items);
                $this->restructLocationField($items);
                $items = $this->changeKeysName($items);
                $items = $this->excludeEmptyValue($items);

                //$items = $this->revertToScalarTagsMatchFields($items);
                AbstractTransformer::recursiveTransformAvatar($items);


                return $this->_handleViewWithData([
                    'users' => $items['friends'],
                    'info' => $info['friends'],
                    'pagination' => $peopleSearchService->getPaginationAdapter($this->getSkip(), $this->getCount(), $info['friends']['totalHits'])
                ]);
            }

            $pagination = [];
            foreach( $people['info']['searchType'] as $keyItems => $infoItems )
            {
                $pagination[$keyItems] = $peopleSearchService->getPaginationAdapter(
                    $this->getSkip(),
                    $this->getCount(),
                    $infoItems['totalHits']
                );
            }

            /** выводим результат */
            return $this->_handleViewWithData(array_merge(
                $people,
                ['pagination' => $pagination]
            ));
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
        $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::NULLED_PARAMS);

        try {
            $targetUserId = $request->get(RequestConstant::TARGET_USER_ID_PARAM, $userId);

            $userContext = null;
            if ($targetUserId != $userId) {

                $userContext = $peopleSearchService->searchProfileById($userId, $targetUserId, $this->getGeoPoint());
                $userContext = !empty($userContext[PeopleSearchMapping::CONTEXT]) ? current($userContext[PeopleSearchMapping::CONTEXT]) : [];

                if (isset($userContext['matchingInterests']) && !empty($userContext['matchingInterests'])) {

                    $tagsArray = explode(',', $userContext['matchingInterests']);
                    foreach ($tagsArray as $tagId)
                    {
                        $keyTag = array_search($tagId, array_column($userContext['tags'], 'id'));
                        $userContext['tagsMatch']['tags'][$tagId] = $userContext['tags'][$keyTag];
                    }

                    $userContext['matchingInterests'] = $userContext['tagsMatch']['tags'];
                    unset($userContext['tagsMatch']['matchingInterests']);
                }
            } else {
                $userContext = $peopleSearchService->searchRecordById(
                    PeopleSearchMapping::CONTEXT,
                    PeopleSearchMapping::AUTOCOMPLETE_ID_PARAM,
                    $userId
                );
            }

            if (isset($userContext['tags']) && !empty($userContext['tags'])) {
                //$script_string = "doc['usersCount'].value + doc['placeCount'].value + doc['eventsCount'].value";
                foreach ($userContext['tags'] as $key => & $itemTag) {
                    $itemTag['sumCount'] = $itemTag['usersCount'] + $itemTag['placeCount'] + $itemTag['eventsCount'];
                }
            }

            if (!is_null($userContext) && !empty($userContext)) {

                if (!is_null($version) && !empty($version) && $version == RequestConstant::DEFAULT_VERSION) {

                    $this->restructTagsField($userContext);
                    $this->restructLocationField($userContext);
                    $userContext = $this->changeKeysName($userContext);
                    $userContext = $this->excludeEmptyValue($userContext);
                    $userContext = $this->revertToScalarTagsMatchFields($userContext);
                    AbstractTransformer::recursiveTransformAvatar($userContext);

                    $userContext = $this->excludeEmptyValue($userContext);
                }

                return $this->_handleViewWithData($userContext);
            }

            return $this->_handleViewWithData([]);

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
