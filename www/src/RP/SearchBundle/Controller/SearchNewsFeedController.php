<?php
/**
 * Контроллер отвечающий за поиск/вывод ленты
 */

namespace RP\SearchBundle\Controller;

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
     * @param string $userId
     * @return Response
     */
    public function getNewsFeedUserEventsAction(Request $request, $userId = null)
    {

    }
}