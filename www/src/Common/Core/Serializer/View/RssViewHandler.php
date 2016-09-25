<?php
namespace Common\Core\Serializer\View;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handler для сереализации и отправке клиенту ответа в виде RSS ленты
 * Его необходимо будет рефакторить для передаче Entity в формате RSS
 */
class RssViewHandler
{
    /**
     * В данном случае этот метод как пример формирования RSS ответа
     * необходимо потом привязать сущность(и) для передачи данных
     *
     * @param ViewHandler $viewHandler
     * @param View        $view
     * @param Request     $request
     * @param string      $format
     *
     * @return Response
     */
    public function createResponse(ViewHandler $handler, View $view, Request $request, $format)
    {
        $rssfeed = '<?xml version="1.0" encoding="UTF-8"?>';
        $rssfeed .= '<rss version="2.0">';
        $rssfeed .= '<channel>';
        $rssfeed .= '<title>My RSS feed</title>';
        $rssfeed .= '<link>http://www.mywebsite.com</link>';
        $rssfeed .= '<description>This is an example RSS feed</description>';
        $rssfeed .= '<language>en-us</language>';
        $rssfeed .= '<copyright>Copyright (C) 2009 mywebsite.com</copyright>';
        return new Response($rssfeed, 200, $view->getHeaders());
    }
}
