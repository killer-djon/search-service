<?php
	
namespace Common\Core\Facade\Loader;

use Symfony\Component\HttpFoundation\Request;

interface ModelLoaderInterface
{
    /**
     * @param $object
     * @param Request $request
     * @return void
     */
    public function load($object, Request $request);
}
