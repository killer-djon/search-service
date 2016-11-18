<?php
/**
 * Класс преобразования данных типов мест
 * т.е. приведение в читабельный вид для совместимости
 */
namespace RP\SearchBundle\Services\Transformers;

use RP\SearchBundle\Services\Mapping\PlaceTypeSearchMapping;

class PlaceTypeTransformer extends AbstractTransformer implements TransformerInterface
{
    /**
     * Набор полей для маппинга
     *
     * @var array $transformMapping
     */
    private $transformMapping = [
        'id'       => PlaceTypeSearchMapping::PLACE_TYPE_ID_FIELD,
        'name'     => PlaceTypeSearchMapping::NAME_FIELD,
        'parentId' => PlaceTypeSearchMapping::PLACE_TYPE_PARENT_ID_FIELD,
    ];

    public function transform(array $dataResult, $context, $subContext = null)
    {
        $listArray = $this->getPlaceTypeData($dataResult[$context]);
        $data = AbstractTransformer::convertToTree($listArray);

        $this->removeParentField($data);

        return AbstractTransformer::array_filter_recursive($data);
    }

    public function removeParentField(&$input)
    {
        if (!empty($input)) {
            foreach ($input as $key => &$value) {
                if (is_array($value)) {
                    $this->removeParentField($value);
                }

                if (isset($value['parentId'])) {
                    unset($value['parentId']);
                }
            }
        }
    }

    public function getPlaceTypeData($placesType)
    {
        $result = [];
        foreach ($placesType as $key => $item) {
            $placeItem = [];
            foreach ($this->transformMapping as $keyField => $fieldName) {
                if (isset($item[$fieldName])) {
                    $placeItem[$keyField] = $item[$fieldName];
                }
            }

            $result[] = $placeItem;
        }

        return $result;
    }

}