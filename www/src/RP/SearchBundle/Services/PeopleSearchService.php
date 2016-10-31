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

        /** добавляем к условию поиска рассчет по совпадению интересов */
        $this->setScriptTagsConditions($currentUser, PeopleSearchMapping::class);

        /** добавляем к условию поиска рассчет расстояния */
        $this->setGeoPointConditions($point, PeopleSearchMapping::class);

        /** формируем условия сортировки */
        $this->setSortingQuery([
            $this->_sortingFactory->getGeoDistanceSort(
                PeopleSearchMapping::LOCATION_POINT_FIELD,
                $point
            ),
        ]);

        /** Получаем сформированный объект запроса */
        $this->setConditionQueryShould([
            $this->_queryConditionFactory->getMultiMatchQuery()
                                         ->setQuery($searchText)
                                         ->setFields(PeopleSearchMapping::getMultiMatchQuerySearchFields()),
            $this->_queryConditionFactory->getWildCardQuery(
                PeopleSearchMapping::FULLNAME_MORPHOLOGY_FIELD,
                $searchText
            )
        ]);

        // Получаем сформированный объект запроса
        $queryMatchResult = $this->createQuery($skip, $count);

        /** поиск документа */
        return $this->searchDocuments($queryMatchResult, PeopleSearchMapping::CONTEXT);
    }

    /**
     * Метод осуществляет поиск людей
     * в заданном городе по ID
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param string $cityId ID города поиска
     * @param GeoPointServiceInterface $point
     * @param string|null $searchText Поисковый запрос
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

        /** добавляем к условию поиска рассчет по совпадению интересов */
        $this->setScriptTagsConditions($currentUser, PeopleSearchMapping::class);

        /** добавляем к условию поиска рассчет расстояния */
        $this->setGeoPointConditions($point, PeopleSearchMapping::class);

        if (!is_null($searchText) && !empty($searchText)) {
            $this->setFilterQuery([
                $this->_queryFilterFactory->getTermFilter([
                    PeopleSearchMapping::LOCATION_CITY_ID_FIELD => $cityId,
                ]),
            ]);

            /** Получаем сформированный объект запроса */
            $query = $this->createMatchQuery(
                $searchText,
                PeopleSearchMapping::getMultiMatchQuerySearchFields(),
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

        return $this->searchDocuments($query, PeopleSearchMapping::CONTEXT);
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
    public function searchPeopleFriends($userId, GeoPointServiceInterface $point, $searchText = null, $skip = 0, $count = null)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        /** добавляем к условию поиска рассчет по совпадению интересов */
        $this->setScriptTagsConditions($currentUser, PeopleSearchMapping::class);

        /** добавляем к условию поиска рассчет расстояния */
        $this->setGeoPointConditions($point, PeopleSearchMapping::class);

        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermsFilter(
                PeopleSearchMapping::FRIEND_LIST_FIELD,
                [$userId]
            ),
        ]);

        /** формируем условия сортировки */
        $this->setSortingQuery([
            $this->_sortingFactory->getGeoDistanceSort(
                PeopleSearchMapping::LOCATION_POINT_FIELD,
                $point
            ),
        ]);

        if (!is_null($searchText) && !empty($searchText)) {

            /** Получаем сформированный объект запроса */
            $queryMatchResult = $this->createMatchQuery(
                $searchText,
                PeopleSearchMapping::getMultiMatchQuerySearchFields(),
                $skip,
                $count
            );

        } else {
            /** Получаем сформированный объект запроса */
            $queryMatchResult = $this->createMatchQuery(null, [], $skip, $count);
        }

        /** поиск документа */
        return $this->searchDocuments($queryMatchResult, PeopleSearchMapping::CONTEXT);
    }

    /**
     * Метод осуществляет поиск в еластике
     * людей которые могут помочь (т.е. с флагом могуПомочь)
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param GeoPointServiceInterface $point
     * @param string $searchText Поисковый запрос
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchPeopleHelpOffers($userId, GeoPointServiceInterface $point, $searchText = null, $skip = 0, $count = null)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        /** добавляем к условию поиска рассчет по совпадению интересов */
        $this->setScriptTagsConditions($currentUser, PeopleSearchMapping::class);
        /** добавляем к условию поиска рассчет расстояния */
        $this->setGeoPointConditions($point, PeopleSearchMapping::class);

        $this->setFilterQuery([
            $this->_queryFilterFactory->getExistsFilter(PeopleSearchMapping::HELP_OFFERS_LIST_FIELD),
        ]);

        if (!is_null($searchText) && !empty($searchText)) {
            $this->setConditionQueryShould([
                $this->_queryConditionFactory->getMultiMatchQuery()
                    ->setQuery($searchText)
                    ->setFields(array_merge(
                        PeopleSearchMapping::getMultiMatchQuerySearchFields(),
                        [
                            PeopleSearchMapping::HELP_OFFERS_NAME_FIELD,
                            PeopleSearchMapping::HELP_OFFERS_NAME_NGRAM_FIELD,
                            PeopleSearchMapping::HELP_OFFERS_NAME_TRANSLIT_FIELD,
                            PeopleSearchMapping::HELP_OFFERS_NAME_TRANSLIT_NGRAM_FIELD,
                        ]
                    )
                ),
                $this->_queryConditionFactory->getWildCardQuery(
                    PeopleSearchMapping::HELP_OFFERS_WORDS_NAME_FIELD,
                    $searchText
                ),
                $this->_queryConditionFactory->getWildCardQuery(
                    PeopleSearchMapping::FULLNAME_MORPHOLOGY_FIELD,
                    $searchText
                ),
            ]);
        }

        $queryMatch = $this->createQuery($skip, $count);

        /** поиск документа */
        return $this->searchDocuments($queryMatch, PeopleSearchMapping::CONTEXT);
    }

}