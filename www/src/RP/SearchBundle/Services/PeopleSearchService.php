<?php
/**
 * Сервис поиска в коллекции пользователей
 */
namespace RP\SearchBundle\Services;

use Common\Core\Constants\Visible;
use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use Common\Core\Facade\Service\User\UserProfileService;
use Elastica\Exception\ElasticsearchException;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
use Common\Core\Facade\Service\Geo\GeoPointService;

class PeopleSearchService extends AbstractSearchService
{

    /**
     * Минимальное значение скора (вес найденного результата)
     *
     * @const string MIN_SCORE_SEARCH
     */
    const MIN_SCORE_SEARCH = '3';

    /**
     * Метод осуществляет поиск в еластике
     * по имени/фамилии пользьвателя
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param string $searchText Поисковый запрос
     * @param GeoPointServiceInterface $point
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchPeopleByName($userId, $searchText, GeoPointServiceInterface $point, $skip = 0, $count = null)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

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

        /** Получаем сформированный объект запроса */
        $queryMatchResult = $this->createMatchQuery(
            $searchText,
            [
                // вариации поля имени
                PeopleSearchMapping::NAME_FIELD,
                PeopleSearchMapping::NAME_NGRAM_FIELD,
                PeopleSearchMapping::NAME_TRANSLIT_FIELD,
                PeopleSearchMapping::NAME_TRANSLIT_NGRAM_FIELD,
                // вариации поля фамилии
                PeopleSearchMapping::SURNAME_FIELD,
                PeopleSearchMapping::SURNAME_NGRAM_FIELD,
                PeopleSearchMapping::SURNAME_TRANSLIT_FIELD,
                PeopleSearchMapping::SURNAME_TRANSLIT_NGRAM_FIELD,
            ],
            $skip,
            $count
        );

        /** устанавливаем все поля по умолчанию */
        $queryMatchResult->setSource(true);

        /** поиск документа */
        return $this->searchDocuments(PeopleSearchMapping::CONTEXT, $queryMatchResult);
    }

    /**
     * Метод осуществляет поиск людей
     * в заданном городе по ID
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param string $cityId ID города поиска
     * @param GeoPointServiceInterface $point
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchPeopleByCityId($userId, $cityId, GeoPointServiceInterface $point, $searchText = null, $skip = 0, $count = null)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        /** формируем условия сортировки */
        $this->setSortingQuery([
            $this->_sortingFactory->getGeoDistanceSort(
                PeopleSearchMapping::LOCATION_POINT_FIELD,
                $point
            ),
        ]);

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

        if (!is_null($point->getRadius()) && $point->isValid()) {
            $this->setFilterQuery([
                $this->_queryFilterFactory->getTermFilter([
                    PeopleSearchMapping::LOCATION_CITY_ID_FIELD => $cityId,
                ]),
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

            /** Получаем сформированный объект запроса */
            $query = $this->createMatchQuery(
                $searchText,
                [
                    // вариации поля имени
                    PeopleSearchMapping::NAME_FIELD,
                    PeopleSearchMapping::NAME_NGRAM_FIELD,
                    PeopleSearchMapping::NAME_TRANSLIT_FIELD,
                    PeopleSearchMapping::NAME_TRANSLIT_NGRAM_FIELD,
                    // вариации поля фамилии
                    PeopleSearchMapping::SURNAME_FIELD,
                    PeopleSearchMapping::SURNAME_NGRAM_FIELD,
                    PeopleSearchMapping::SURNAME_TRANSLIT_FIELD,
                    PeopleSearchMapping::SURNAME_TRANSLIT_NGRAM_FIELD,
                ],
                $skip,
                $count
            );
        } else {
            /** задаем условия поиска по городу */
            $this->setConditionQueryMust([
                $this->_queryConditionFactory->getTermQuery(PeopleSearchMapping::LOCATION_CITY_ID_FIELD, $cityId),
            ]);

            /** Получаем сформированный объект запроса */
            $query = $this->createQuery($skip, $count);
        }

        // устанавливаем все поля по умолчанию
        $query->setSource(true);

        return $this->searchDocuments(PeopleSearchMapping::CONTEXT, $query);
    }

    /**
     * Метод осуществляет поиск в еластике
     * по имени/фамилии пользьвателя находяхищся в друзьях
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param string $searchText Поисковый запрос
     * @param GeoPointServiceInterface $point
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchPeopleFriends($userId, $searchText, GeoPointServiceInterface $point, $skip = 0, $count = null)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

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

        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermsFilter(
                PeopleSearchMapping::FRIEND_LIST_FIELD,
                [$userId]
            ),
        ]);

        if (!empty($searchText)) {

            /** Получаем сформированный объект запроса */
            $queryMatchResult = $this->createMatchQuery(
                $searchText,
                [
                    // вариации поля имени
                    PeopleSearchMapping::NAME_FIELD,
                    PeopleSearchMapping::NAME_NGRAM_FIELD,
                    PeopleSearchMapping::NAME_TRANSLIT_FIELD,
                    PeopleSearchMapping::NAME_TRANSLIT_NGRAM_FIELD,
                    // вариации поля фамилии
                    PeopleSearchMapping::SURNAME_FIELD,
                    PeopleSearchMapping::SURNAME_NGRAM_FIELD,
                    PeopleSearchMapping::SURNAME_TRANSLIT_FIELD,
                    PeopleSearchMapping::SURNAME_TRANSLIT_NGRAM_FIELD,
                ],
                $skip,
                $count
            );

        } else {
            /** Получаем сформированный объект запроса */
            $queryMatchResult = $this->createMatchQuery(null, [], $skip, $count);
        }

        /** устанавливаем все поля по умолчанию */
        $queryMatchResult->setSource(true);

        /** поиск документа */
        return $this->searchDocuments(PeopleSearchMapping::CONTEXT, $queryMatchResult);
    }

}