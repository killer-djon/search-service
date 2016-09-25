<?php
namespace Common\Core\Serializer;

use JMS\Serializer\Annotation\XmlKeyValuePairs;

/**
 * XML обертка для корректной сериализации массива с помощью аннотации XmlKeyValuePairs
 */
class XMLWrapper
{
    /**
     * @var array
     * @XmlKeyValuePairs
     */
    public $data = array();
}
