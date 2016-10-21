<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 21.10.16
 * Time: 15:50
 */

namespace RP\SearchBundle\Services;

use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use Common\Core\Facade\Service\User\UserProfileService;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;

trait SearchServiceTrait
{
    /**
     * Часто используемое условие получения поля скрипта
     * для рассчета совпадения по интересу
     *
     * @param \Common\Core\Facade\Service\User\UserProfileService $currentUser Объект текущего пользователя
     * @return void
     */
    public function setScriptTagsField(UserProfileService $currentUser)
    {
        $this->setScriptFields([
            'tagsInPercent' => $this->_scriptFactory->getTagsIntersectInPercentScript(
                PeopleSearchMapping::TAGS_ID_FIELD,
                $currentUser->getTags()
            ),
            'tagsCount'     => $this->_scriptFactory->getTagsIntersectScript(
                PeopleSearchMapping::TAGS_ID_FIELD,
                $currentUser->getTags()
            ),
        ]);
    }

    /**
     * Часто используемое условие по локации
     * учитывается сортировка по расстоянию, рассчет расстояния
     * или поиск в радиусе
     *
     * @param \Common\Core\Facade\Service\User\UserProfileService $currentUser Объект текущего пользователя
     * @return void
     */
    public function setGeoPointConditions(GeoPointServiceInterface $point)
    {
        if (!is_null($point->getRadius()) && $point->isValid()) {
            /** формируем условия сортировки */
            $this->setSortingQuery([
                $this->_sortingFactory->getGeoDistanceSort(
                    PeopleSearchMapping::LOCATION_POINT_FIELD,
                    $point
                ),
            ]);

            $this->setScriptFields([
                'distance'          => $this->_scriptFactory->getDistanceScript(
                    PeopleSearchMapping::LOCATION_POINT_FIELD,
                    $point
                ),
                'distanceInPercent' => $this->_scriptFactory->getDistanceInPercentScript(
                    PeopleSearchMapping::LOCATION_POINT_FIELD,
                    $point
                ),
            ]);

            $this->setFilterQuery([
                $this->_queryFilterFactory->getGeoDistanceFilter(
                    PeopleSearchMapping::LOCATION_POINT_FIELD,
                    [
                        'lat' => $point->getLatitude(),
                        'lon' => $point->getLongitude(),
                    ],
                    $point->getRadius(),
                    'm'
                ),
            ]);
        }
    }
}