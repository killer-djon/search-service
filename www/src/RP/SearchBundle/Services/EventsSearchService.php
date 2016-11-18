<?php
/**
 * Сервис поиска событий по различным критериям
 */
namespace RP\SearchBundle\Services;

use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use RP\SearchBundle\Services\Mapping\EventsSearchMapping;

class EventsSearchService extends AbstractSearchService
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
    public function searchEventsByName($userId, $searchText, GeoPointServiceInterface $point, $skip = 0, $count = null)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        $this->setConditionQueryShould([
            $this->_queryConditionFactory->getMultiMatchQuery()
                                         ->setQuery($searchText)
                                         ->setFields(EventsSearchMapping::getMultiMatchQuerySearchFields()),

            $this->_queryConditionFactory->getWildCardQuery(
                EventsSearchMapping::NAME_WORDS_NAME_FIELD,
                $searchText
            ),
            $this->_queryConditionFactory->getWildCardQuery(
                EventsSearchMapping::TYPE_WORDS_FIELD,
                $searchText
            ),
            $this->_queryConditionFactory->getWildCardQuery(
                EventsSearchMapping::TAG_WORDS_FIELD,
                $searchText
            ),
        ]);

        /** добавляем к условию поиска рассчет по совпадению интересов */
        $this->setScriptTagsConditions($currentUser, EventsSearchMapping::class);

        /** добавляем к условию поиска рассчет расстояния */
        $this->setGeoPointConditions($point, EventsSearchMapping::class);

        if ($point->isValid()) {
            /** формируем условия сортировки */
            $this->setSortingQuery(
                $this->_sortingFactory->getGeoDistanceSort(
                    EventsSearchMapping::LOCATION_POINT_FIELD,
                    $point
                )
            );
        }

        $queryMatch = $this->createQuery($skip, $count);

        return $this->searchDocuments($queryMatch, EventsSearchMapping::CONTEXT);
    }

    /**
     * Метод осуществляет поиск в еластике
     * собыий в заданном городе
     * если задан поисковый запрос то в этом городе фильтруем еще и по запросу
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param string $cityId ID города
     * @param GeoPointServiceInterface $point
     * @param string $searchText Поисковый запрос
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchEventsByCity($userId, $cityId, GeoPointServiceInterface $point, $searchText = null, $skip = 0, $count = null)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        if (!is_null($searchText) && !empty($searchText)) {
            $this->setConditionQueryShould([
                $this->_queryConditionFactory->getMultiMatchQuery()
                                             ->setQuery($searchText)
                                             ->setFields(EventsSearchMapping::getMultiMatchQuerySearchFields()),
                $this->_queryConditionFactory->getWildCardQuery(
                    EventsSearchMapping::NAME_WORDS_NAME_FIELD,
                    $searchText
                ),
                $this->_queryConditionFactory->getWildCardQuery(
                    EventsSearchMapping::TYPE_WORDS_FIELD,
                    $searchText
                ),
                $this->_queryConditionFactory->getWildCardQuery(
                    EventsSearchMapping::TAG_WORDS_FIELD,
                    $searchText
                ),
            ]);
        }

        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermFilter([EventsSearchMapping::LOCATION_CITY_ID_FIELD => $cityId]),
        ]);

        /** добавляем к условию поиска рассчет по совпадению интересов */
        $this->setScriptTagsConditions($currentUser, EventsSearchMapping::class);

        /** добавляем к условию поиска рассчет расстояния */
        $this->setGeoPointConditions($point, EventsSearchMapping::class);

        if ($point->isValid()) {
            /** формируем условия сортировки */
            $this->setSortingQuery(
                $this->_sortingFactory->getGeoDistanceSort(
                    EventsSearchMapping::LOCATION_POINT_FIELD,
                    $point
                )
            );
        }

        $queryMatch = $this->createQuery($skip, $count);

        return $this->searchDocuments($queryMatch, EventsSearchMapping::CONTEXT);
    }

    /**
     * Получаем единственную запись из базы события
     * по заданному ID
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param string $eventId ID искомого события
     * @return array|null
     */
    public function getEventById($userId, $eventId)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        return $this->searchRecordById(EventsSearchMapping::CONTEXT, EventsSearchMapping::EVENT_ID_FIELD, $eventId);
    }

    /**
     * Метод поиска событий по заданным местам
     * так же учитывая поисковый запрос
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param array $places Массив ID мест где ищем события
     * @param GeoPointServiceInterface $point
     * @param string $searchText Поисковый запрос
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchEventsByPlacesId($userId, $places, GeoPointServiceInterface $point, $searchText = null, $skip = 0, $count = null)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        if (!is_null($searchText) && !empty($searchText)) {
            $this->setConditionQueryShould([
                $this->_queryConditionFactory->getMultiMatchQuery()
                                             ->setQuery($searchText)
                                             ->setFields(EventsSearchMapping::getMultiMatchQuerySearchFields()),
                $this->_queryConditionFactory->getWildCardQuery(
                    EventsSearchMapping::NAME_WORDS_NAME_FIELD,
                    $searchText
                ),
                $this->_queryConditionFactory->getWildCardQuery(
                    EventsSearchMapping::TYPE_WORDS_FIELD,
                    $searchText
                ),
                $this->_queryConditionFactory->getWildCardQuery(
                    EventsSearchMapping::TAG_WORDS_FIELD,
                    $searchText
                ),
            ]);
        }

        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermsFilter(EventsSearchMapping::PLACE_ID_FIELD, $places)
        ]);

        /** добавляем к условию поиска рассчет по совпадению интересов */
        $this->setScriptTagsConditions($currentUser, EventsSearchMapping::class);

        /** добавляем к условию поиска рассчет расстояния */
        $this->setGeoPointConditions($point, EventsSearchMapping::class);

        if ($point->isValid()) {
            /** формируем условия сортировки */
            $this->setSortingQuery(
                $this->_sortingFactory->getGeoDistanceSort(
                    EventsSearchMapping::LOCATION_POINT_FIELD,
                    $point
                )
            );
        }

        $queryMatch = $this->createQuery($skip, $count);

        return $this->searchDocuments($queryMatch, EventsSearchMapping::CONTEXT);

    }
}