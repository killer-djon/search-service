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
    /**
     * На сколько ячеек мы разбиваем карту
     * т.е. сколько ячеек сетки
     *
     * @const int CHUNK_BOUND_BOXES
     */
    const CHUNK_BOUND_BOXES = 4;

    /**
     * Начальная позиция массива поступившего
     *
     * @var int $position
     */
    private $position = 0;

    /**
     * Искходный массив
     *
     * @var array $array
     */
    private $array = [];

    /**
     * начальный радиус
     *
     * @var int $radius
     */
    private $radius;

    public function __construct(array $array)
    {
        $this->array = $array;
        $this->position = 0;
    }

    public function setRadius($radius)
    {
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

    /**
     * Разбиваем массив на многомерный массив
     * где учитываются расстояния x,y
     *
     * @return array
     */
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

        if (max($this->array) < $this->radius) {
            array_push($ranges, [max($this->array), $this->radius]);
        }

        return $ranges;
    }

    /**
     * Получаем массив значений широты/долготы
     * всех квадрантов сетки на карте
     * по ней формируем геоточки сетки
     *
     * @return array Массив точек
     */
    public function getBoundBoxesRange()
    {
        $ranges = array_chunk($this->getRange(), self::CHUNK_BOUND_BOXES);
        $this->fillChildArrayWithDistance($ranges); // дополняем массив недостающими расстояниями

        // расстояние в метрах из которых формируем сетку
        $divider = ceil($this->radius / $this->position);

        // массив клеток
        $boundBoxes = [];

        // перебираем верхний уровень массива
        // это у нас кол-во клеток на сетке

        for ($x = 0; $x < count($ranges); $x++) {
            // внутри перебираем ячейки
            // т.е. кол-во клеток = count($ranges) * self::CHUNK_BOUND_BOXES = 16 например

            for ($y = 0; $y < self::CHUNK_BOUND_BOXES; $y++) {
                $boundBoxes[] = [
                    [$divider * $x, $divider * $y],
                    [$divider * ($x + 1), $divider * ($y + 1)],
                ];
            }
        }

        return $boundBoxes;
    }

    /**
     * Дополняем массив точке недостающими значениями
     *
     * @param array $ranges Исходный массив точек
     * @return void
     */
    public function fillChildArrayWithDistance(&$ranges)
    {
        $lastBox = $ranges[count($ranges) - 1];
        if (count($lastBox) < self::CHUNK_BOUND_BOXES) {

            // последний максимальный массив, от него и пляшем
            $lastChildBox = $lastBox[count($lastBox) - 1];
            while (count($lastBox) !== self::CHUNK_BOUND_BOXES) {
                array_push($lastBox, [
                    max($lastChildBox),
                    (max($lastChildBox) + max($lastChildBox) - min($lastChildBox)),
                ]);

                $lastChildBox = $lastBox[count($lastBox) - 1];
            }

            $ranges[count($ranges) - 1] = $lastBox;
        }
    }
}