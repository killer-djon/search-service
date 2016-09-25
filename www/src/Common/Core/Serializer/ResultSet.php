<?php
namespace Common\Core\Serializer;

/**
 * Набор возвращаемых данных для сериализации в json.
 *
 */
class ResultSet extends \ArrayObject implements \JsonSerializable
{
    public function appendArray(array $array)
    {
        foreach ($array as $key => $value) {
            $this[$key] = $value;
        }
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }
}
