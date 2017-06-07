<?php
/**
 * Сервис поиска по условиям городов
 */

namespace RP\SearchBundle\Services;

use Common\Core\Constants\Location;
use Common\Util\VarDumper;
use Elastica\Query\MultiMatch;
use RP\SearchBundle\Services\Mapping\CountrySearchMapping;

/**
 * Class CountrySearchService
 * @package RP\SearchBundle\Services
 */
class CountrySearchService extends AbstractSearchService
{
    /**
     * @const int
     */
    const DEFAULT_SKIP_COUNTRIES = 0;

    /**
     * @const int
     */
    const DEFAULT_COUNT_COUNTRIES = 3;

    /**
     * @return \FOS\ElasticaBundle\Elastica\Index
     */
    private function getElasticaRussianplacePrivateIndex()
    {
        return $this->container->get('fos_elastica.index.russianplace_private');
    }

    /**
     * Метод осуществляет поиск в еластике
     * по названию города
     *
     * @param string $searchText Поисковый запрос
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @param bool $fullScan Значение указывает на то, нужно ли сканировать все индексы эластика, или только russianplace_private
     * @return array Массив с найденными результатами
     */
    public function searchCountryByName($searchText, $skip = 0, $count = null, $fullScan = false)
    {
        $this->setConditionQueryMust([
            $this->_queryConditionFactory->getMultiMatchQuery()
                                         ->setFields([
                                             $this->setBoostField(CountrySearchMapping::NAME_FIELD, 5),
                                             $this->setBoostField(CountrySearchMapping::INTERNATIONAL_NAME_FIELD, 4),
                                             $this->setBoostField(CountrySearchMapping::TRANSLIT_NAME_FIELD, 3),
                                         ])
                                         ->setQuery(mb_strtolower($searchText))
                                         ->setOperator(MultiMatch::OPERATOR_OR)
                                         ->setType(MultiMatch::TYPE_PHRASE_PREFIX),
        ]);

        $this->setSortingQuery([
            // $this->_sortingFactory->getFieldSort('_score', 'desc'),
            $this->_sortingFactory->getFieldSort(CountrySearchMapping::NAME_FIELD)
        ]);

        /** Получаем сформированный объект запроса */
        $queryMatchResult = $this->createQuery($skip, $count);

        /** поиск документа */
        return $this->searchDocuments($queryMatchResult, CountrySearchMapping::CONTEXT);
    }
}
