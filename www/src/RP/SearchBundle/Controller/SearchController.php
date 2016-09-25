<?php

namespace RP\SearchBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Common\Core\Controller\ApiController;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

class SearchController extends ApiController
{
    public function searchUserAction(Request $request, $searchString)
    {
        return $this->_handleViewWithData([
	        'searchText' => $searchString
        ]);
    }
}
