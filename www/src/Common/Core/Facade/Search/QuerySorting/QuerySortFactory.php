<?php
/**
 * Класс инициализирующий объекты сортировки
 */
namespace Common\Core\Facade\Search\QuerySorting;

use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use Elastica\AbstractScript;

class QuerySortFactory implements QuerySortFactoryInterface
{

    /**
     * Формирование условия сортировки по дистанции
     *
     * @param string $pointField Название поля содержащее geo_point тип
     * @param \Common\Core\Facade\Service\Geo\GeoPointServiceInterface $point Объект гео
     * @param string $order (asc|desc)
     * @param string $unit Метка меры расстояния
     * @param string $distanceType Применяемый тип для сортировки по дистанции (sloppy_arc, arc, plane)
     * @return array
     */
    public function getGeoDistanceSort($pointField, GeoPointServiceInterface $point, $order = 'asc', $unit = 'km', $distanceType = 'plane')
    {
        return [
            '_geo_distance' => [
                $pointField     => [
                    'lat' => $point->getLatitude(),
                    'lon' => $point->getLongitude(),
                ],
                'order'         => $order,
                'unit'          => $unit,
                'distance_type' => $distanceType,
            ],
        ];
    }

    /**
     * Сортировка по несущестсвующим полям
     * т.е. если при сортировке по полю в каком-либо документе
     * этого поля нет, то мы его либо в конец кладем либо в начало (asc|desc)
     *
     * @param string $fieldName Название поля
     * @param string $fieldType Тип несуществующего поля ( long, string, number, date ... )
     * @param string $order (asc|desc)
     * @return array
     */
    public function getMappedIgnoringSort($fieldName, $fieldType = 'string', $order = 'asc')
    {
        return [
            $fieldName => [
                'unmapped_type' => $fieldType,
                'order'         => $order,
            ],
        ];
    }

    /**
     * Обычная сортировка по какому-либо полю
     *
     * @param string $fieldName Название поля
     * @param string $order (asc|desc)
     * @return array
     */
    public function getFieldSort($fieldName, $order = 'asc')
    {
        return [
            $fieldName => [
                'order' => $order,
            ],
        ];
    }

    /**
     * Сортировка результата на основе скрипта
     * т.е. динамическая сортировка на вычесляемых значениях
     * это необходимо допустим для сортировки рассчитывая дистанцию но не выводить ее
     * или например сортировка вычисляемого результата (допустим сортировать по возсрасту * 2)
     *
     * @param AbstractScript $script Скрипт сортировки
     * @param string $order Sort order (asc|desc)
     * @param string $sortFieldType Тип вычисляемого поля сортировки ( number, string, long, date ... )
     * @return array
     */
    public function getScriptingSort(AbstractScript $script, $order = 'asc', $sortFieldType = 'string')
    {
        return [
            "_script" => [
                'type'   => $sortFieldType,
                'script' => $script,
                'order'  => $order,
            ],
        ];
    }

    /**
     * Сортировка с применением аггрегированной функции
     *
     * @param string $fieldName Название поля
     * @param string $order Sort order (asc|desc)
     * @param string $order Sort mode (min, max, avg, sum, median), default: min
     * @return array
     */
    public function getFieldModSort($fieldName, $order = 'asc', $mode = 'min')
    {
        return [
            $fieldName => [
                'order' => $order,
                'mode'  => $mode,
            ],
        ];
    }

    /**
     * Сортировка по несуществующим данным в поле
     *
     * @param string $fieldName Название поля
     * @param string $sortOrder (asc|desc)
     * @return array
     */
    public function getMissingSort($fieldName, $sortOrder = 'asc')
    {
        $sortOrder = ($sortOrder == 'asc' ? '_last' : '_first');

        return [
            $fieldName => [
                'missing' => $sortOrder,
            ],
        ];
    }

}