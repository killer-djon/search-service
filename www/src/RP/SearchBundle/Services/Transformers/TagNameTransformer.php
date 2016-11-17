<?php
/**
 * Класс преобразования данных тегов
 */
namespace RP\SearchBundle\Services\Transformers;

use RP\SearchBundle\Services\Mapping\TagNameSearchMapping;

class TagNameTransformer extends AbstractTransformer implements TransformerInterface
{
    /**
     * Набор полей для маппинга
     *
     * @var array $transformMapping
     */
    private $transformMapping = [
        'id'       => TagNameSearchMapping::TAG_NAME_ID_FIELD,
        'name'     => TagNameSearchMapping::NAME_FIELD,
        'usersCount' => TagNameSearchMapping::USERS_COUNT_FIELD,
        'placeCount' => TagNameSearchMapping::PLACE_COUNT_FIELD,
        'eventsCount' => TagNameSearchMapping::EVENTS_COUNT_FIELD,
        'sumCount' => TagNameSearchMapping::TOTAL_FIELD
    ];


    public function transform(array $dataResult, $context, $subContext = null)
    {
        $result = [];
        foreach ($dataResult[$context] as $key => $item) {
            $tagItems = [];
            foreach ($this->transformMapping as $keyField => $fieldName) {
                if (isset($item[$fieldName])) {
                    $tagItems[$keyField] = $item[$fieldName];
                }
            }

            $result[] = $tagItems;
        }

        return $result;
    }
}