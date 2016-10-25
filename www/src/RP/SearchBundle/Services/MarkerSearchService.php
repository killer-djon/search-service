<?php
/**
 * Сервис поиска маркеров по заданным фильтрам
 */
namespace RP\SearchBundle\Services;

use Common\Core\Constants\Visible;
use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use Elastica\Exception\ElasticsearchException;
use Common\Core\Facade\Service\Geo\GeoPointService;
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
        PlaceSearchMapping::CONTEXT => PlaceSearchMapping::class
    ];

    /**
     * Поиск маркеров по задданым типам
     * в поиске могут присутствовать несколько типов
     *
     * @param array $filters По каким типам делаем поиск
     * @param string $searchText Поисковый запрос
     */
    public function searchMarkersByTypes(array $filters, $searchText, $skip = 0, $count = null)
    {
        array_walk($filters, function($filter) use (&$searchTypes){
            array_key_exists($filter, $this->filterTypes) && $searchTypes[$filter] = $this->filterTypes[$filter]::getMultiTypeSearchFields();
        });

        if(!is_null($searchTypes) && !empty($searchTypes))
        {
            array_walk($searchTypes, function($sType) use (&$fields){
                $fields = array_merge((array)$fields, $sType);
            });
            $types = array_keys($searchTypes);

            // $fields - получили общий список полей для поиска
            // $types - общий список типов в индексе
            /*$this->setFilterQuery(array_map(function($type){
                return $this->_queryFilterFactory->getTypeFilter($type);
            }, $types));
            */
            /** Получаем сформированный объект запроса */
            $queryMatchResult = $this->createMatchQuery(
                $searchText,
                [],
                $skip,
                $count
            );

            return $this->searchMultiTypeDocuments($queryMatchResult, $searchTypes);
        }
    }
}