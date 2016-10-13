<?php
/**
 * Сервис осуществления поиска мест с разными параметрами
 */
namespace RP\SearchBundle\Services;

use Common\Core\Constants\Visible;
use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use Elastica\Exception\ElasticsearchException;
use RP\SearchBundle\Services\Mapping\PlaceSearchMapping;

class PlacesSearchService extends AbstractSearchService
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
    public function searchPlacesByName($userId, $searchText, GeoPointServiceInterface $point, $skip = 0, $count = null)
    {
        /** получаем текущего ползователя */
        $currentUser = $this->getUserById($userId);

        $this->setConditionQueryShould([
            $this->_queryConditionFactory->getMultiMatchQuery()->setQuery($searchText)->setFields([
                PlaceSearchMapping::NAME_FIELD,
                PlaceSearchMapping::NAME_NGRAM_FIELD,
                PlaceSearchMapping::NAME_TRANSLIT_FIELD,
                PlaceSearchMapping::NAME_TRANSLIT_NGRAM_FIELD
            ]),
            $this->_queryConditionFactory->getWildCardQuery(
                PlaceSearchMapping::NAME_WORDS_FIELD,
                $searchText
            )
        ]);

        if( $point->isValid() )
        {
            $this->setScriptFields([
                'distance'          => $this->_scriptFactory->getDistanceScript(
                    PlaceSearchMapping::LOCATION_POINT_FIELD,
                    $point
                )
            ]);

            $this->setSortingQuery([
                $this->_sortingFactory->getGeoDistanceSort(
                    PlaceSearchMapping::LOCATION_POINT_FIELD,
                    $point
                ),
            ]);
        }

        $queryMatch = $this->createQuery($skip, $count);

        /** отображаем все поля изначальные */
        $queryMatch->setSource(true);

        return $this->searchDocuments(PlaceSearchMapping::CONTEXT, $queryMatch);
    }
}