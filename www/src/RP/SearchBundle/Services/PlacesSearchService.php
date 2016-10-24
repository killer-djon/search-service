<?php
/**
 * Сервис осуществления поиска мест с разными параметрами
 */
namespace RP\SearchBundle\Services;

use Common\Core\Constants\ModerationStatus;
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
	    
        $this->setConditionQueryShould([
            $this->_queryConditionFactory->getMultiMatchQuery()->setQuery($searchText)->setFields([
                PlaceSearchMapping::NAME_FIELD,
                PlaceSearchMapping::NAME_NGRAM_FIELD,
                PlaceSearchMapping::NAME_TRANSLIT_FIELD,
                PlaceSearchMapping::NAME_TRANSLIT_NGRAM_FIELD,
                PlaceSearchMapping::TYPE_NAME_FIELD,
                PlaceSearchMapping::TYPE_NAME_NGRAM_FIELD,
                PlaceSearchMapping::TYPE_NAME_TRANSLIT_FIELD,
                PlaceSearchMapping::TYPE_NAME_TRANSLIT_NGRAM_FIELD,
                PlaceSearchMapping::TAG_NAME_FIELD,
                PlaceSearchMapping::TAG_NAME_NGRAM_FIELD,
                PlaceSearchMapping::TAG_NAME_TRANSLIT_FIELD,
                PlaceSearchMapping::TAG_NAME_TRANSLIT_NGRAM_FIELD
            ]),
            $this->_queryConditionFactory->getWildCardQuery(
                PlaceSearchMapping::DESCRIPTION_FIELD,
                $searchText
            ),
            $this->_queryConditionFactory->getWildCardQuery(
                PlaceSearchMapping::NAME_WORDS_FIELD,
                $searchText
            ),
            $this->_queryConditionFactory->getWildCardQuery(
                PlaceSearchMapping::TYPE_WORDS_FIELD,
                $searchText
            ),
            $this->_queryConditionFactory->getWildCardQuery(
                PlaceSearchMapping::TAG_WORDS_FIELD,
                $searchText
            )
        ]);
        
        /** устанавливаем фильтры только для мест */
        $this->setFilterPlaces($userId);

        if ($point->isValid()) {
            $this->setScriptFields([
                'distance' => $this->_scriptFactory->getDistanceScript(
                    PlaceSearchMapping::LOCATION_POINT_FIELD,
                    $point
                ),
            ]);

            $this->setSortingQuery([
                $this->_sortingFactory->getGeoDistanceSort(
                    PlaceSearchMapping::LOCATION_POINT_FIELD,
                    $point
                ),
            ]);
            
            if(!is_null($point->getRadius()))
            {
	            $this->setFilterQuery([
	                $this->_queryFilterFactory->getGeoDistanceFilter(
	                    PlaceSearchMapping::LOCATION_POINT_FIELD,
	                    [
	                        'lat' => $point->getLatitude(),
	                        'lon' => $point->getLongitude(),
	                    ],
	                    $point->getRadius(),
	                    'm'
	                )
	            ]);
            }
        }

        $queryMatch = $this->createQuery($skip, $count);

        /** отображаем все поля изначальные */
        $queryMatch->setSource(true);

        return $this->searchDocuments(PlaceSearchMapping::CONTEXT, $queryMatch);
    }
    
    /**
     * Метод осуществляет поиск в еластике
     * мест по городу
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param string $cityId ID города
     * @param GeoPointServiceInterface $point
     * @param string $searchText Поисковый запрос
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchPlacesByCity($userId, $cityId, GeoPointServiceInterface $point, $searchText = null, $skip = 0, $count = null)
    {
	    $this->setConditionQueryMust([
		    $this->_queryConditionFactory->getTermQuery(PlaceSearchMapping::LOCATION_CITY_ID_FIELD, $cityId)
	    ]);

        if( !is_null($searchText) )
        {
            $this->setConditionQueryShould([
                $this->_queryConditionFactory->getMultiMatchQuery()->setQuery($searchText)->setFields([
                    PlaceSearchMapping::NAME_FIELD,
                    PlaceSearchMapping::NAME_NGRAM_FIELD,
                    PlaceSearchMapping::NAME_TRANSLIT_FIELD,
                    PlaceSearchMapping::NAME_TRANSLIT_NGRAM_FIELD,
                    PlaceSearchMapping::TYPE_NAME_FIELD,
                    PlaceSearchMapping::TYPE_NAME_NGRAM_FIELD,
                    PlaceSearchMapping::TYPE_NAME_TRANSLIT_FIELD,
                    PlaceSearchMapping::TYPE_NAME_TRANSLIT_NGRAM_FIELD,
                    PlaceSearchMapping::TAG_NAME_FIELD,
                    PlaceSearchMapping::TAG_NAME_NGRAM_FIELD,
                    PlaceSearchMapping::TAG_NAME_TRANSLIT_FIELD,
                    PlaceSearchMapping::TAG_NAME_TRANSLIT_NGRAM_FIELD
                ]),
                $this->_queryConditionFactory->getWildCardQuery(
                    PlaceSearchMapping::DESCRIPTION_FIELD,
                    $searchText
                ),
                $this->_queryConditionFactory->getWildCardQuery(
                    PlaceSearchMapping::NAME_WORDS_FIELD,
                    $searchText
                ),
                $this->_queryConditionFactory->getWildCardQuery(
                    PlaceSearchMapping::TYPE_WORDS_FIELD,
                    $searchText
                ),
                $this->_queryConditionFactory->getWildCardQuery(
                    PlaceSearchMapping::TAG_WORDS_FIELD,
                    $searchText
                )
            ]);
        }
	     /** устанавливаем фильтры только для мест */
        $this->setFilterPlaces($userId);
        
        $queryMatch = $this->createQuery($skip, $count);
        
        return $this->searchDocuments(PlaceSearchMapping::CONTEXT, $queryMatch);
        
    }
    
    /**
	 * Установка фильтров для поиска только мест
	 * без бонусов и скидок только места
	 * которые так же прошли модерацию (т.е. не удалены)   
	 * @param string $userId ID пользователя (автор места)
	 * @return void
     */
    private function setFilterPlaces($userId)
    {
	    $this->setFilterQuery([
	        $this->_queryFilterFactory->getBoolOrFilter([
		        $this->_queryFilterFactory->getTermFilter([
			        PlaceSearchMapping::BONUS_FIELD => ''
		        ]),
		        $this->_queryFilterFactory->getNotFilter(
			        $this->_queryFilterFactory->getExistsFilter(PlaceSearchMapping::BONUS_FIELD)
		        )
	        ]),
	        $this->_queryFilterFactory->getBoolOrFilter([
		        $this->_queryFilterFactory->getTermFilter([PlaceSearchMapping::DISCOUNT_FIELD => 0]),
		        $this->_queryFilterFactory->getNotFilter(
			        $this->_queryFilterFactory->getExistsFilter(PlaceSearchMapping::DISCOUNT_FIELD)
		        )
	        ]),
	        $this->_queryFilterFactory->getBoolOrFilter([
		        // или это автор и статус вывести на модерации
		        $this->_queryFilterFactory->getBoolAndFilter([
			        $this->_queryFilterFactory->getTermFilter([
			        	PlaceSearchMapping::AUTHOR_ID_FIELD => $userId
			        ]),
			        $this->_queryFilterFactory->getTermsFilter(
				        PlaceSearchMapping::MODERATION_STATUS_FIELD, [
					        ModerationStatus::OK,
					        ModerationStatus::RESTORED,
					        ModerationStatus::DIRTY,
					        ModerationStatus::REJECTED,
					        ModerationStatus::NOT_IN_PROMO
				        ]
			        )
		        ]),
		        // или если не автор то 
		        $this->_queryFilterFactory->getBoolAndFilter([
			        $this->_queryFilterFactory->getNotFilter(
				        $this->_queryFilterFactory->getTermFilter([
				        	PlaceSearchMapping::AUTHOR_ID_FIELD => $userId
				        ])
			        ),
			        $this->_queryFilterFactory->getTermsFilter(
				        PlaceSearchMapping::MODERATION_STATUS_FIELD, [
					        ModerationStatus::OK,
							ModerationStatus::RESTORED
				        ]
			        )
		        ]),
	        ])
        ]);
	}

	/**
     * Метод осуществляет поиск в еластике
     * скидочных мест
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param GeoPointServiceInterface $point
     * @param string $searchText Поисковый запрос
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchPlacesByDiscount($userId, GeoPointServiceInterface $point, $searchText = null, $skip = 0, $count = null)
    {
        /** получаем текущего ползователя */
        $currentUser = $this->getUserById($userId);
        if (!is_null($searchText) && !empty($searchText)) {
            $this->setConditionQueryShould([
                $this->_queryConditionFactory->getMultiMatchQuery()->setQuery($searchText)->setFields([
                    PlaceSearchMapping::NAME_FIELD,
                    PlaceSearchMapping::NAME_NGRAM_FIELD,
                    PlaceSearchMapping::NAME_TRANSLIT_FIELD,
                    PlaceSearchMapping::NAME_TRANSLIT_NGRAM_FIELD,
                ]),
                $this->_queryConditionFactory->getWildCardQuery(
                    PlaceSearchMapping::NAME_WORDS_FIELD,
                    $searchText
                ),
                $this->_queryConditionFactory->getWildCardQuery(
                    PlaceSearchMapping::DESCRIPTION_FIELD,
                    $searchText
                ),
                $this->_queryConditionFactory->getWildCardQuery(
                    PlaceSearchMapping::BONUS_FIELD,
                    $searchText
                ),
            ]);
        }
        $this->setFilterQuery([
            $this->_queryFilterFactory->getRangeFilter(PlaceSearchMapping::DISCOUNT_FIELD, 1, 100),
            $this->_queryFilterFactory->getExistsFilter(PlaceSearchMapping::BONUS_FIELD)
        ]);

        $this->setHighlightQuery([
            'description'   => []
        ]);
        $queryMatch = $this->createQuery($skip, $count);

        /** отображаем все поля изначальные */
        $queryMatch->setSource(true);

        return $this->searchDocuments(PlaceSearchMapping::CONTEXT, $queryMatch);
    }
}