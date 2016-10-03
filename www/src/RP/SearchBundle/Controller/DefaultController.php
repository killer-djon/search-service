<?php

namespace RP\SearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('RPSearchBundle:Default:index.html.twig');
    }
}
