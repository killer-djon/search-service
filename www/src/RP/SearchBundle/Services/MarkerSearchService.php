<?php
/**
 * Сервис поиска маркеров по заданным фильтрам
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

class MarkerSearchService extends AbstractSearchService
{
    /**
     * Доступные для поиска типы фильтров
     *
     * @var array $filterTypes
     */
    private $filterTypes = [
        PeopleSearchMapping::CONTEXT => PeopleSearchMapping::class,
        PlaceSearchMapping::CONTEXT => PlaceSearchMapping::class,
        HelpOffersSearchMapping::CONTEXT => HelpOffersSearchMapping::class,
        DiscountsSearchMapping::CONTEXT => DiscountsSearchMapping::class
    ];

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

        array_walk($filters, function($filter) use (&$searchTypes){
            array_key_exists($filter, $this->filterTypes) && $searchTypes[$filter] = $this->filterTypes[$filter]::getMultiMatchQuerySearchFields();
        });

        if(!is_null($searchTypes) && !empty($searchTypes))
        {
            $queryMatchResults = [];
            foreach($searchTypes as $keyType => $typeFields)
            {
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
            return $this->searchMultiTypeDocuments($queryMatchResults, $searchTypes);
        }
    }
}