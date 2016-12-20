<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 14.12.16
 * Time: 15:03
 */

namespace Common\Core\Facade\Service;

class RangeIterator implements \Iterator
{
    private $position = 0;

    private $array = [];

    private $radius;

    public function __construct(array $array)
    {
        $this->array = $array;
        $this->position = 0;
    }

    public function setRadius($radius){
        $this->radius = $radius;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->array[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->array[$this->position]);
    }

    public function getRange()
    {
        $ranges = [];
        $this->rewind();

        while ($this->valid()) {
            $key = $this->key();
            $current = $this->current();
            if (isset($this->array[$key + 1])) {
                $ranges[] = [$current, $this->array[$key + 1]];
            }

            $this->next();
        }

        if( max($this->array) < $this->radius ){
            array_push($ranges, [max($this->array), $this->radius]);
        }

        return $ranges;
    }
}