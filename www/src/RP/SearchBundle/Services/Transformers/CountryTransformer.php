<?php

namespace RP\SearchBundle\Services\Transformers;

use RP\SearchBundle\Services\Mapping\CountrySearchMapping;

/**
 * Class CountryTransformer
 * @package RP\SearchBundle\Services\Transformers
 */
class CountryTransformer extends AbstractTransformer implements TransformerInterface
{

    /**
     * Набор полей для маппинга
     *
     * @var array $transformMapping
     */
    private $transformMapping = [
        'id'   => CountrySearchMapping::ID_FIELD,
        'name' => CountrySearchMapping::NAME_FIELD,
    ];

    /**
     * Трансформируем данные в соответсвии с заданным маппингом полей
     *
     * @param array $dataResult Набор данных для преобразования
     * @param string $context Контекст массива (т.е. ключ ассоц.массива)
     * @param string $subContext Это если есть вложенность, нам нужен ключ вложенного объекта
     * @return array
     */
    public function transform(array $dataResult, $context, $subContext = null)
    {
        $result = [];

        if (!isset($dataResult[$context]) || is_null($dataResult[$context])) {
            return [];
        }

        foreach ($dataResult[$context] as $key => $objItem) {
            $countryItem = [];
            foreach ($this->transformMapping as $fieldKey => $fieldName) {
                if (is_array($fieldName)) {
                    $arValue = array_map(function ($item) use ($objItem) {
                        return AbstractTransformer::path($objItem, $item);
                    }, $fieldName);

                    $countryItem[$fieldKey] = implode(', ', $arValue);

                } else {
                    $countryItem[$fieldKey] = AbstractTransformer::path($objItem, $fieldName);

                }
            }

            $result[] = $countryItem;
        }

        return $result;
    }
}