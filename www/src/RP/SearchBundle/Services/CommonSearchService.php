<?php
/**
 * Общий сервис поиска
 * с помощью которого будем искать как глобально так и маркеры
 */
namespace RP\SearchBundle\Services;

use Common\Core\Constants\Visible;
use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use Elastica\Exception\ElasticsearchException;
use Common\Core\Facade\Service\Geo\GeoPointService;
use RP\SearchBundle\Services\Mapping\DiscountsSearchMapping;
use RP\SearchBundle\Services\Mapping\HelpOffersSearchMapping;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
use RP\SearchBundle\Services\Mapping\PlaceSearchMapping;

class CommonSearchService extends AbstractSearchService
{

    public function commonSearchByFilters(
        $userId,
        $searchText = null,
        $cityId = null,
        GeoPointServiceInterface $point = null,
        $filterType = null,
        $skip = 0,
        $count = null
    ) {
        $currentUser = $this->getUserById($userId);

        if(is_null($filterType))
        {
            /**
             * Массив объектов запроса
             * ключами в массиве служит тип поиска (в какой коллекции искать надо)
             */
            $queryMatchResults = [];

            /**
             * Если не задана категория поиска
             * тогда ищем во всех коллекциях еластика по условиям
             */
            foreach($this->filterTypes as $keyType => $type)
            {
                $this->clearScriptFields();
                $this->clearFilter();

                $this->setFilterQuery($type::getMatchSearchFilter($this->_queryFilterFactory, $userId));
                $this->setScriptTagsConditions($currentUser, $type);
                $this->setGeoPointConditions($point, $type);

                if( !is_null($cityId) && !empty($cityId) && !is_null($type::LOCATION_CITY_ID_FIELD) )
                {
                    $this->setFilterQuery([
                        $this->_queryFilterFactory->getTermFilter([
                            $type::LOCATION_CITY_ID_FIELD => $cityId
                        ])
                    ]);
                }

                /**
                 * Получаем сформированный объект запроса
                 * когда запрос многотипный НЕТ необходимости
                 * указывать skip и count
                 */
                $queryMatchResults[$keyType] = $this->createMatchQuery(
                    $searchText,
                    $type::getMultiMatchQuerySearchFields()
                );
            }


            /**
             * Так же при вызове метода поиска для многотипных
             * поисков НЕТ необходимости передавать контекст поиска
             * т.е. тип в котором ищем, надо искать везде
             */
            return $this->searchMultiTypeDocuments($queryMatchResults);
        }

        if( !is_null($cityId) && !empty($cityId) && !is_null($this->filterTypes[$filterType]::LOCATION_CITY_ID_FIELD) )
        {
            $this->setFilterQuery([
                $this->_queryFilterFactory->getTermFilter([
                    $this->filterTypes[$filterType]::LOCATION_CITY_ID_FIELD => $cityId
                ])
            ]);
        }

        $this->setFilterQuery($this->filterTypes[$filterType]::getMatchSearchFilter($this->_queryFilterFactory, $userId));
        $this->setScriptTagsConditions($currentUser, $this->filterTypes[$filterType]);
        $this->setGeoPointConditions($point, $this->filterTypes[$filterType]);

        $queryMatch = $this->createMatchQuery(
            $searchText,
            $this->filterTypes[$filterType]::getMultiMatchQuerySearchFields(),
            $skip,
            $count
        );


        return $this->searchDocuments($queryMatch, $this->searchTypes[$filterType], true, $filterType);
    }

    /**
     * Поиск маркеров по задданым типам
     * в поиске могут присутствовать несколько типов
     *
     * @param string $userId
     * @param array $filters По каким типам делаем поиск
     * @param string $searchText Поисковый запрос
     * @return array Массив с найденными результатами
     */
    public function searchMarkersByTypes($userId, array $filters, GeoPointServiceInterface $point, $searchText = null)
    {
        $currentUser = $this->getUserById($userId);

        array_walk($filters, function ($filter) use (&$searchTypes) {
            array_key_exists($filter, $this->filterTypes) && $searchTypes[$filter] = $this->filterTypes[$filter]::getMultiMatchQuerySearchFields();
        });

        if (!is_null($searchTypes) && !empty($searchTypes)) {
            $queryMatchResults = [];
            foreach ($searchTypes as $keyType => $typeFields) {
                $this->clearScriptFields();
                $this->clearFilter();

                $this->setFilterQuery($this->filterTypes[$keyType]::getMatchSearchFilter($this->_queryFilterFactory, $userId));
                $this->setScriptTagsConditions($currentUser, $this->filterTypes[$keyType]);
                $this->setGeoPointConditions($point, $this->filterTypes[$keyType]);

                /** формируем условия сортировки */
                $this->setSortingQuery([
                    $this->_sortingFactory->getGeoDistanceSort(
                        $this->filterTypes[$keyType]::LOCATION_POINT_FIELD,
                        $point
                    ),
                ]);

                /**
                 * Получаем сформированный объект запроса
                 * когда запрос многотипный НЕТ необходимости
                 * указывать skip и count
                 */
                $queryMatchResults[$keyType] = $this->createMatchQuery(
                    $searchText,
                    $typeFields
                );
            }

            /**
             * Так же при вызове метода поиска для многотипных
             * поисков НЕТ необходимости передавать контекст поиска
             * т.е. тип в котором ищем, надо искать везде
             */
            return $this->searchMultiTypeDocuments($queryMatchResults);
        }
    }
}