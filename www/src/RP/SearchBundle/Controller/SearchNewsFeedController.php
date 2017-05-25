<?php
/**
 * Контроллер отвечающий за поиск/вывод ленты
 */

namespace RP\SearchBundle\Controller;

use Common\Core\Constants\NewsFeedSections;
use Common\Core\Controller\ApiController;
use Common\Core\Constants\RequestConstant;
use RP\SearchBundle\Services\Mapping\PostSearchMapping;
use RP\SearchBundle\Services\NewsFeedSearchService;
use RP\SearchBundle\Services\Traits\PeopleServiceTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Common\Core\Exceptions\SearchServiceException;

class SearchNewsFeedController extends ApiController
{
    use PeopleServiceTrait;

    /**
     * Получаем ленту по ID стены
     *
     * @param Request $request
     * @param string $wallId
     * @return Response
     */
    public function getNewsFeedPostsAction(Request $request, $wallId)
    {

        try {
            /** @var ID пользователя */
            $userId = $this->getRequestUserId();
            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::DEFAULT_VERSION);

            /** @var NewsFeedSearchService */
            $postSearchService = $this->getNewsFeedSearchService();
            $posts = $postSearchService->searchPostsByWallId($userId, $wallId, $this->getSkip(), $this->getCount());

            if (empty($posts)) {
                return $this->_handleViewWithData([]);
            }

            if ($version == RequestConstant::DEFAULT_VERSION) {
                return $this->_handleViewWithData($posts[PostSearchMapping::CONTEXT]);
            }

            return $this->_handleViewWithData([
                'info'       => $postSearchService->getTotalHits(),
                'pagination' => $postSearchService->getPaginationAdapter(
                    $this->getSkip(),
                    $this->getCount()
                ),
                'items'      => $posts[PostSearchMapping::CONTEXT],
            ]);

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Получаем пост по его ID
     *
     * @param Request $request
     * @param string postId
     * @return Response
     */
    public function getNewsFeedPostByIdAction(Request $request, $postId)
    {
        try {
            $userId = $this->getRequestUserId();
            $postSearchService = $this->getNewsFeedSearchService();

            $post = $postSearchService->searchPostById($userId, $postId);

            if (empty($post)) {
                return $this->_handleViewWithData(new \stdClass());
            }

            return $this->_handleViewWithData($post);
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Получаем пользовательские события
     * если не задан userId то смотрим свои события
     * если он задан то мы смотрим события другого профиля (в случае если он разрешает это делать)
     *
     * @param Request $request
     * @return Response
     */
    public function getNewsFeedUserEventsAction(Request $request)
    {
        /*$friendIds = $this->_userRelationsReportingService->getFriendIdsFor($userId);
        $followedUserIds = $this->_userRelationsReportingService->getFollowedUsersIdsFor($userId);
        // друзья, преследователи и сам пользователь
        $friendIds = array_merge($friendIds, $followedUserIds, [$userId]);
        $eventTypes = $this->getUserEventsGroups(NewsFeedSections::FEED_NEWS);

        $criteria = [$this->getUserFeedCriteria($userId, $friendIds, $eventTypes)];
        $criteria = [new CompositeConditionHack($criteria),];

        $order = [
            UserEventDocumentMapping::CREATED_AT_FIELD => ReportingSortingDirection::DESC,
        ];

        //@var $posts LoggableCursor
        $posts = $this->_reportingRepository->select(
            UserEventDocumentMapping::CONTEXT,
            $criteria,
            $order,
            $count > 0 ? (int)$count : self::USER_EVENTS_COUNT_ON_PAGE,
            $skip > 0 ? (int)$skip : 0
        );

        if ($posts->count(true) < $count) {
            $isLastPage = true;
        }

        return $posts;*/

        try {
            $userId = $this->getRequestUserId();

            /*$this->getPeopleSearchService()->setSourceFields(['id']);
            $friendIds = $this->getPeopleSearchService()->searchPeopleFriends(
                $userId,
                $userId,
                ['friends'],
                $this->getGeoPoint()
            );*/

            $userProfile = $this->getPeopleSearchService()->getUserById($this->getUser()->getUserName());
            $friendIds = $userProfile->getFriendList();
            // вставляем в массив ID друзей и ID самого себя
            array_push($friendIds, $userId);

            $eventTypes = $this->getUserEventsGroups(NewsFeedSections::FEED_NEWS);

            $newsFeedSearchService = $this->getNewsFeedSearchService();
            $userEvents = $newsFeedSearchService->searchUserEventsByUserId(
                $userId,
                $eventTypes,
                $friendIds
            );

            return $this->_handleViewWithData($userEvents);
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }

    }
}