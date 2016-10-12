<?php
/**
 * Основной контроллер поиска по местам
 */
namespace RP\SearchBundle\Controller;

use Common\Core\Controller\ApiController;
use Common\Core\Controller\ControllerTrait;
use Elastica\Exception\ElasticsearchException;
use Symfony\Component\HttpFoundation\Request;
use Common\Core\Constants\RequestConstant;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SearchPlacesController extends ApiController
{

}