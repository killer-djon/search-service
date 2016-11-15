<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 15.11.16
 * Time: 11:15
 */

namespace RP\SearchBundle\Services\Transformers;

interface TransformerInterface
{
    /**
     * Трансформируем данные в соответсвии с заданным маппингом полей
     *
     * @param array $dataResult Набор данных для преобразования
     * @param string $context Контекст массива (т.е. ключ ассоц.массива)
     * @return array
     */
    public function transform(array $dataResult, $context);
}