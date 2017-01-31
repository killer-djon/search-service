<?php
/**
 * Трейт который просто формирует повторяющиеся условия запроса
 */

namespace RP\SearchBundle\Services;

use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use Common\Core\Facade\Service\User\UserProfileService;

trait SearchServiceTrait
{
    /**
     * Часто используемое условие получения поля скрипта
     * для рассчета совпадения по интересу
     *
     * @param \Common\Core\Facade\Service\User\UserProfileService $currentUser Объект текущего пользователя
     * @param object $classMapping Класс маппинга
     * @return void
     */
    public function setScriptTagsConditions(UserProfileService $currentUser, $classMapping)
    {
        $this->setScriptFields([
            'tagsInPercent' => $this->_scriptFactory->getTagsIntersectInPercentScript(
                $classMapping::TAGS_ID_FIELD,
                $currentUser->getTags()
            ),
            'tagsCount'     => $this->_scriptFactory->getTagsIntersectScript(
                $classMapping::TAGS_ID_FIELD,
                $currentUser->getTags()
            ),
        ]);
    }

    /**
     * Выводим массив интересов которые совпадают у пользователя
     * и найденного результата
     *
     * @param \Common\Core\Facade\Service\User\UserProfileService $currentUser Объект текущего пользователя
     * @param object $classMapping Класс маппинга
     * @return void
     */
    public function setScriptMatchInterestsConditions(UserProfileService $currentUser, $classMapping)
    {
        $this->setScriptFields([
            'matchingInterests' => $this->_scriptFactory->getMatchingInterestsScript(
                $classMapping::TAGS_ID_FIELD,
                $currentUser->getTags()
            )
        ]);
    }


    /**
     * Часто используемое условие по локации
     * учитывается сортировка по расстоянию, рассчет расстояния
     * или поиск в радиусе
     *
     * @param \Common\Core\Facade\Service\Geo\GeoPointServiceInterface $point Объект геопозиционирования
     * @param object $classMapping Класс маппинга
     * @param string $unit
     * @return void
     */
    public function setGeoPointConditions(GeoPointServiceInterface $point, $classMapping, $unit = 'm')
    {
        if ($point->isValid()) {

            $this->setScriptFields([
                'distance'          => $this->_scriptFactory->getDistanceScript(
                    $classMapping::LOCATION_POINT_FIELD,
                    $point
                ),
                'distanceInPercent' => $this->_scriptFactory->getDistanceInPercentScript(
                    $classMapping::LOCATION_POINT_FIELD,
                    $point
                ),
            ]);

            if (!is_null($point->getRadius()) && !empty($point->getRadius())) {
                $this->setFilterQuery([
                    $this->_queryFilterFactory->getGeoDistanceFilter(
                        $classMapping::LOCATION_POINT_FIELD,
                        [
                            'lat' => $point->getLatitude(),
                            'lon' => $point->getLongitude(),
                        ],
                        $point->getRadius(),
                        (string)$unit
                    ),
                ]);
            }
        }
    }
}