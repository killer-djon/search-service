<?php
/**
 * Хелпер для формирования ответов api в режиме обратной совместимости
 */

namespace RP\SearchBundle\Helper;

/**
 * Class BackwardCompatibilityHelper
 * @package RP\SearchBundle\Helper
 */
class BackwardCompatibilityHelper
{

    /**
     * @param array $places
     * @param int|null $countryId
     * @param int|null $cityId
     * @return array
     */
    public static function preparePromoPlaces(array $places, $countryId = null, $cityId = null)
    {
        $result = [
            'list' => [],
            'meta' => [],
        ];

        if (!empty($cityId)) {
            // если показываем места для города

            $countryCount = [];
            $cityCount = [];

            foreach ($places as $place) {
                $push = false;

                if (isset($place['term_aggregation']['buckets']) && !empty($place['term_aggregation']['buckets'])) {
                    $cities = $place['term_aggregation']['buckets'];

                    foreach ($cities as $city) {
                        if ((int)$city['key'] === (int)$cityId) {
                            $push = true;

                            $items = static::getPlacesFromHits($city);

                            if (!empty($items)) {
                                $result['list'] = array_merge($result['list'], $items);
                            }

                            $cityCount[$city['key']] = $city['doc_count'];
                        }
                    }
                }

                if ($push) {
                    // старый формат (сортировка на стороне клиента из-за того, что json упорядочивает ключи объекта по алфавиту)
                    $countryCount[$place['key']] = $place['doc_count'];
                }
            }

            $result['meta']['cityCount'] = $cityCount;
            $result['meta']['countryCount'] = $countryCount;

        } elseif (!empty($countryId)) {
            // если показываем места для страны

            $countryCount = [];
            $cityCount = [];

            foreach ($places as $place) {
                if ((int)$place['key'] === (int)$countryId) {
                    if (isset($place['term_aggregation']['buckets']) && !empty($place['term_aggregation']['buckets'])) {
                        $cities = $place['term_aggregation']['buckets'];

                        foreach ($cities as $city) {
                            $items = static::getPlacesFromHits($city);

                            if (!empty($items)) {
                                $result['list'] = array_merge($result['list'], $items);
                            }

                            $cityCount[$city['key']] = $city['doc_count'];
                        }
                    }

                    // старый формат (сортировка на стороне клиента из-за того, что json упорядочивает ключи объекта по алфавиту)
                    $countryCount[$place['key']] = $place['doc_count'];
                }
            }

            $result['meta']['cityCount'] = $cityCount;
            $result['meta']['countryCount'] = $countryCount;

        } else {
            // если показываем места для всех стран

            $countryCount = [];

            foreach ($places as $place) {
                $items = static::getPlacesFromHits($place);

                if (!empty($items)) {
                    $result['list'] = array_merge($result['list'], $items);
                }

                // старый формат (сортировка на стороне клиента из-за того, что json упорядочивает ключи объекта по алфавиту)
                $countryCount[$place['key']] = $place['doc_count'];
            }

            $result['meta']['countryCount'] = $countryCount;
        }

        return $result;
    }

    /**
     * @param array $parent
     * @return array
     */
    public static function getPlacesFromHits(array $parent)
    {
        $result = [];

        if (isset($parent['places'])
            && isset($parent['places']['hits'])
            && isset($parent['places']['hits']['hits'])
            && !empty($parent['places']['hits']['hits'])
        ) {
            foreach ($parent['places']['hits']['hits'] as $row) {
                $fields = $row['fields'];

                $data = $row['_source'];
                $data['tagsMatch'] = [
                    'distance'    => $fields['distance'][0],
                    'distancePct' => $fields['distanceInPercent'][0],
//                    'tags'        => $fields['tagsCount'][0],
//                    'tagsPct'     => $fields['tagsInPercent'][0],
                ];

                $result[] = $data;
            }
        }

        return $result;
    }
}
