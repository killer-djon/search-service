<?php
/**
 * Контроллер отвечающий за поиск/вывод ленты
 */

namespace RP\SearchBundle\Controller;

use Common\Core\Controller\ApiController;
use Common\Core\Constants\RequestConstant;
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
     *
     * @param Request $request
     * @param string $wallId
     *
     * @return Response
     */
    public function getNewsFeedPostsAction(Request $request, $wallId)
    {
        //$userEventsGroup = $this->getUserEventsGroups();
    }


    /**
     * Получаем пользовательские события
     * если не задан userId то смотрим свои события
     * если он задан то мы смотрим события другого профиля (в случае если он разрешает это делать)
     *
     *
     * @param Request $request
     * @param string $userId
     *
     * @return Response
     */
    public function getNewsFeedUserEventsAction(Request $request, $userId)
    {

    }
}