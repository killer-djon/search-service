<?php
/**
 * Основной сервиса поиска людей в еластике
 * формирование условий запроса к еластику
 */
namespace RP\SearchBundle\Services\Transformers;

use RP\SearchBundle\Services\Mapping\CitySearchMapping;

class CityTransformer extends AbstractTransformer implements TransformerInterface
{

    /**
     * Набор полей для маппинга
     *
     * @var array $transformMapping
     */
    private $transformMapping = [
        'id'        => CitySearchMapping::ID_FIELD,
        'name'      => [
            CitySearchMapping::NAME_FIELD,
            CitySearchMapping::COUNTRY_NAME_FIELD,
        ],
        'latitude'  => CitySearchMapping::CENTER_CITY_POINT_LATITUDE_FIELD,
        'longitude' => CitySearchMapping::CENTER_CITY_POINT_LONGITUDE_FIELD
    ];

    /**
     * Трансформируем данные в соответсвии с заданным маппингом полей
     *
     * @param array $dataResult Набор данных для преобразования
     * @param string $context Контекст массива (т.е. ключ ассоц.массива)
     * @return array
     */
    public function transform(array $dataResult, $context)
    {
        $result = [];

        if (!isset($dataResult[$context]) || is_null($dataResult[$context])) {
            return [];
        }

        foreach ($dataResult[$context] as $key => $objItem) {
            $cityItem = [];
            foreach ($this->transformMapping as $fieldKey => $fieldName) {
                if (is_array($fieldName)) {
                    $arValue = array_map(function ($item) use ($objItem) {
                        return AbstractTransformer::path($objItem, $item);
                    }, $fieldName);

                    $cityItem[$fieldKey] = implode(', ', $arValue);

                } else {
                    $cityItem[$fieldKey] = AbstractTransformer::path($objItem, $fieldName);

                }
            }

            $result[] = $cityItem;
        }

        return $result;
    }
}