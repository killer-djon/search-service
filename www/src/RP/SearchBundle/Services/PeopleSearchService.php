<?php
/**
 * Сервис поиска в коллекции пользователей
 */
namespace RP\SearchBundle\Services;

use Common\Core\Constants\Visible;
use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
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
    const MIN_SCORE_SEARCH = '2';

    /**
     * Метод осуществляет поиск в еластике
     * по имени/фамилии пользьвателя
     *
     * @param string $searchText Поисковый запрос
     * @param GeoPointServiceInterface $point
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchPeopleByName($searchText, GeoPointServiceInterface $point, $skip = 0, $count = null)
    {
        /** формируем условия сортировки */
        $this->setSortingQuery([
            $this->_sortingFactory->getGeoDistanceSort(
                PeopleSearchMapping::LOCATION_POINT_FIELD,
                $point
            )
        ]);

        $this->setScriptFields([
            'distance' => $this->_scriptFactory->getDistanceScript(
                PeopleSearchMapping::LOCATION_POINT_FIELD,
                $point
            )
        ]);

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

        /** устанавливаем минимальное значение для веса */
        $queryMatchResult->setMinScore(self::MIN_SCORE_SEARCH);

        /** устанавливаем все поля по умолчанию */
        $queryMatchResult->setSource(true);

        /** поиск документа */
        return $this->searchDocuments(PeopleSearchMapping::CONTEXT, $queryMatchResult);
    }

    /**
     * Метод осуществляет поиск людей
     * в заданном городе по ID
     *
     * @param string $cityId ID города поиска
     * @param GeoPointServiceInterface $point
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchPeopleByCityId($cityId, GeoPointServiceInterface $point, $skip = 0, $count = null)
    {
        // добавляем скрипты коорые выводятся в доп. полях
        $this->setScriptFields([
            'distance' => $this->_scriptFactory->getScript(
                "
                if (!doc[\"location.point\"].empty) {
                    doc[\"location.point\"].distanceInKm({$point->getLatitude()}, {$point->getLongitude()})
                }else{
                    0.0;
                }
                "
            ),
        ]);
        /** задаем условия поиска по городу */
        $this->setConditionQueryMust([
            $this->_queryConditionFactory->getTermQuery(PeopleSearchMapping::LOCATION_CITY_ID_FIELD, $cityId),
        ]);

        $query = $this->createQuery($skip, $count);
        // сортируем результат в момент получения данных
        $query->setSort([
            '_geo_distance' => [
                'location.point' => [
                    'lat' => $point->getLatitude(),
                    'lon' => $point->getLongitude()
                ],
                'order' => 'asc',
                'unit'  => 'km',
                'distance_type' => 'plane'
            ]
        ]);
        // устанавливаем все поля по умолчанию
        $query->setSource(true);

        return $this->searchDocuments(PeopleSearchMapping::CONTEXT, $query);
    }

    /**
     * Метод осуществляет поиск в еластике
     * по имени/фамилии пользьвателя
     *
     * @param string $userId ID пользователя который посылает запрос
     * @param string $searchText Поисковый запрос
     * @param GeoPointServiceInterface $point
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchPeopleFriends($userId, $searchText, GeoPointServiceInterface $point, $skip = 0, $count = null)
    {
        return [];
    }

    /**
     * Возвращаем набор полей для поиска по совпадению
     *
     * @return array $fields
     */
    private function getMatchQueryFields()
    {
        return [
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
        ];
    }
}