<?php
/**
 * Контроллер отвечающий за поиск/вывод ленты
 */

namespace RP\SearchBundle\Controller;

use Common\Core\Constants\NewsFeedSections;
use Common\Core\Controller\ApiController;
use Common\Core\Constants\RequestConstant;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
use RP\SearchBundle\Services\Mapping\PostSearchMapping;
use RP\SearchBundle\Services\Mapping\UserEventSearchMapping;
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

            /** @var Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM);
            $searchText = $searchText ?? RequestConstant::NULLED_PARAMS;

            /** @var NewsFeedSearchService */
            $postSearchService = $this->getNewsFeedSearchService();
            $posts = $postSearchService->searchPostsByWallId(
                $userId,
                $wallId,
                $searchText,
                $this->getSkip(),
                $this->getCount()
            );

            if (empty($posts)) {
                return $this->_handleViewWithData([]);
            }

            $result = $this->getNewFormatResponse(
                $postSearchService,
                PostSearchMapping::CONTEXT
            );

            /** Временный костыль, убираем HTML из текста для мобильных платформ */
            $headerUserAgent = $request->headers->get('platform', $request->headers->get('User-Agent'));

            if (!empty($result) && preg_match('/(ios|android)/i', $headerUserAgent)) {
                function unTagsMessage(&$array)
                {
                    foreach ($array as $key => & $item) {
                        if (stripos($key, 'message') !== false && !empty($item)) {
                            $item = is_string($item) ? strip_tags($item, '<em>') : [strip_tags(current($item), '<em>')];
                        }

                        if (is_array($item)) {
                            unTagsMessage($item);
                        }
                    }
                }

                unTagsMessage($result['items']);
            }


            return $this->_handleViewWithData($result);

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

            /** Временный костыль, убираем HTML из текста для мобильных платформ */
            $headerUserAgent = $request->headers->get('platform', $request->headers->get('User-Agent'));

            if (preg_match('/(ios|android)/i', $headerUserAgent)) {
                $post['message'] = (!empty($post['message']) ? strip_tags($post['message']) : '');
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
        try {
            $userId = $this->getRequestUserId();

            $userProfile = $this->getPeopleSearchService()->getUserById(
                $this->getUser()->getUserName(),
                $userId != PeopleSearchMapping::RP_USER_ID ? true : [
                    'excludes' => ['friendList', 'relations']
                ]
            );

            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM);

            $friendIds = $userProfile->getFriendList() ?? [PeopleSearchMapping::RP_USER_ID];

            $eventTypes = $this->getUserEventsGroups(NewsFeedSections::FEED_NEWS);

            $newsFeedSearchService = $this->getNewsFeedSearchService();
            $userEvents = $newsFeedSearchService->searchUserEventsByUserId(
                $userId,
                $eventTypes,
                $searchText ?? null,
                $friendIds,
                $this->getSkip(),
                $this->getCount()
            );

            if (empty($userEvents)) {
                return $this->_handleViewWithData([]);
            }

            $result = $this->getNewFormatResponse(
                $newsFeedSearchService,
                UserEventSearchMapping::CONTEXT
            );

            /** Временный костыль, убираем HTML из текста для мобильных платформ */
            $headerUserAgent = $request->headers->get('platform', $request->headers->get('User-Agent'));

            if (!empty($result) && preg_match('/(ios|android)/i', $headerUserAgent)) {

                function unTagsMessage(&$array)
                {
                    foreach ($array as $key => & $item) {
                        if (stripos($key, 'message') !== false && !empty($item)) {
                            $item = is_string($item) ? strip_tags($item, '<em>') : [strip_tags(current($item), '<em>')];
                        }

                        if (is_array($item)) {
                            unTagsMessage($item);
                        }
                    }
                }

                unTagsMessage($result['items']);
            }

            return $this->_handleViewWithData($result);
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }

    }


    public function getPostsListAction(Request $request, $cityId = null)
    {
        try{
            $userId = $this->getRequestUserId();

            /** @var Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM);
            $searchText = !empty($searchText) ? $searchText : RequestConstant::NULLED_PARAMS;

            $postService = $this->getNewsFeedSearchService();
            $posts = $postService->getPostLists(
                $userId,
                $cityId,
                $searchText,
                $this->getSkip(),
                $this->getCount()
            );

            if (empty($posts)) {
                return $this->_handleViewWithData([]);
            }

            $result = $this->getNewFormatResponse(
                $postService,
                PostSearchMapping::CONTEXT
            );

            return $this->_handleViewWithData($result);

        }catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Получение постов
     * по категории и локации
     *
     * @param Request $request
     * @param string $rpUserId
     * @param string $cityId
     * @param string $categoryId
     * @return Response
     */
    public function getCategoryPostAction(Request $request, $rpUserId, $categoryId = null, $cityId = null)
    {
        try {
            $userId = $this->getRequestUserId();

            /** @var Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM);
            $searchText = !empty($searchText) ? $searchText : RequestConstant::NULLED_PARAMS;

            $postService = $this->getNewsFeedSearchService();
            $posts = $postService->getPostCategoriesByParams(
                $userId,
                $rpUserId,
                $categoryId,
                $cityId,
                $searchText,
                $this->getSkip(),
                $this->getCount()
            );

            if (empty($posts)) {
                return $this->_handleViewWithData([]);
            }

            $result = $this->getNewFormatResponse(
                $postService,
                PostSearchMapping::CONTEXT
            );

            /** Временный костыль, убираем HTML из текста для мобильных платформ */
            $headerUserAgent = $request->headers->get('platform', $request->headers->get('User-Agent'));

            if (!empty($result) && preg_match('/(ios|android)/i', $headerUserAgent)) {
                function unTagsMessage(&$array)
                {
                    foreach ($array as $key => & $item) {
                        if (stripos($key, 'message') !== false && !empty($item)) {
                            $item = is_string($item) ? strip_tags($item, '<em>') : [strip_tags(current($item), '<em>')];
                        }

                        if (is_array($item)) {
                            unTagsMessage($item);
                        }
                    }
                }

                unTagsMessage($result['items']);
            }

            return $this->_handleViewWithData($result);

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Получаем уведомления пользователя по ленте
     *
     * @param Request $request Оъект запроса
     * @return Response
     */
    public function getNewsFeedNotificationsAction(Request $request)
    {
        try {
            /** @var string ID текущего пользователя */
            $userId = $this->getRequestUserId();
            // какие уведомления нам вообще надо возвращать
            $eventTypes = $this->getUserEventsGroups(NewsFeedSections::FEED_NOTIFICATIONS);
            /** @var NewsFeedSearchService Сервис поиска ленты/уведомлений */
            $userFeedService = $this->getNewsFeedSearchService();
            $notifications = $userFeedService->getNewsFeedNotifications(
                $userId,
                $eventTypes,
                $this->getSkip(),
                $this->getCount()
            );

            return $this->_handleViewWithData(
                $this->getNewFormatResponse(
                    $userFeedService,
                    UserEventSearchMapping::CONTEXT
                )
            );

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }
}