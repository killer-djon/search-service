<?php
/**
 * Контроллер отвечающий за поиск/вывод ленты
 */
namespace RP\SearchBundle\Controller;

use Common\Core\Controller\ApiController;
use Common\Core\Constants\RequestConstant;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Common\Core\Exceptions\SearchServiceException;

class SearchNewsFeedController extends ApiController
{
    public function getNewsFeedByFilterAction(Request $request, $filter)
    {

    }
}