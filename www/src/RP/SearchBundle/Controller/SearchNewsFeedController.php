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
            $searchText = !empty($searchText) ? $searchText : RequestConstant::NULLED_PARAMS;

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


            return $this->_handleViewWithData(
                $this->getNewFormatResponse($postSearchService, PostSearchMapping::CONTEXT)
            );

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

            $userProfile = $this->getPeopleSearchService()->getUserById($this->getUser()->getUserName());
            $friendIds = $userProfile->getFriendList();

            $eventTypes = $this->getUserEventsGroups(NewsFeedSections::FEED_NEWS);

            $newsFeedSearchService = $this->getNewsFeedSearchService();
            $userEvents = $newsFeedSearchService->searchUserEventsByUserId(
                $userId,
                $eventTypes,
                $friendIds,
                $this->getSkip(),
                $this->getCount()
            );

            $result = $this->getNewFormatResponse(
                $newsFeedSearchService,
                UserEventSearchMapping::CONTEXT
            );

            /** Временный костыль, убираем HTML из текста для мобильных платформ */
            $headerUserAgent = $request->headers->get('platform', $request->headers->get('User-Agent'));

            if (!empty($result) && preg_match('/(ios|android)/i', $headerUserAgent)) {
                array_walk($result['items'], function (&$item) {
                    $item['message'] = (!empty($item['message']) ? strip_tags($item['message']) : '');
                });
            }

            return $this->_handleViewWithData($result);
        } catch (SearchServiceException $e) {
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

            return $this->_handleViewWithData($this->getNewFormatResponse($postService, PostSearchMapping::CONTEXT));

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }
}