<?php
/**
 * Exception for incorrect format of routing
 */
namespace Common\Core\Exceptions;

use Symfony\Component\Serializer\Exception\BadMethodCallException;

class ResponseFormatException extends BadMethodCallException
{
    protected $message = 'Format must be set on the routing';
}
