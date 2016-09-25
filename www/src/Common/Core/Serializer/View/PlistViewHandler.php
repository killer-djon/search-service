<?php
namespace Common\Core\Serializer\View;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use CFPropertyList\CFPropertyList;
use CFPropertyList\CFTypeDetector;
use CFPropertyList\PListException;

/**
 * Обрабатываем данные для передачи в plist формате
 */
class PlistViewHandler
{
    /**
     * @param ViewHandler $viewHandler
     * @param View $view
     * @param Request $request
     * @param string $format
     *
     * @return Response
     */
    public function createResponse(ViewHandler $handler, View $view, Request $request, $format)
    {
        $binaryPlist = null;
        try
        {
            $plist = new CFPropertyList();
            $typeDetector = new CFTypeDetector(['objectToArrayMethod' => 'getArrayCopy']);

            /** @var $data \Common\Core\Serializer\XMLWrapper */
            $data = $view->getData();
            $guessedStructure = $typeDetector->toCFType($data->data);
            $plist->add($guessedStructure);
            ini_set('mbstring.internal_encoding', '');
            $binaryPlist = $plist->toBinary();
            ini_set('mbstring.internal_encoding', 'UTF-8');

            if (is_null($view->getStatusCode()))
            {
                $view->setStatusCode(Response::HTTP_OK);
            }
        }
        catch(PListException $e)
        {
            $view->setStatusCode( $e->getCode() );
        }

        return new Response($binaryPlist, $view->getStatusCode(), $view->getHeaders());
    }
}
