<?php
/**
 * Сервис осуществления поиска мест с разными параметрами
 */

namespace RP\SearchBundle\Services;

use Common\Core\Constants\ModerationStatus;
use Common\Core\Constants\SortingOrder;
use Common\Core\Constants\Visible;
use Common\Core\Facade\Search\QueryScripting\QueryScriptFactory;
use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use Elastica\Query\MultiMatch;
use RP\SearchBundle\Helper\BackwardCompatibilityHelper;
use RP\SearchBundle\Services\Mapping\PlaceSearchMapping;
use RP\SearchBundle\Services\Mapping\PlaceTypeSearchMapping;

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
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        $this->setConditionQueryShould([
            $this->_queryConditionFactory->getMultiMatchQuery()
                                         ->setQuery($searchText)
                                         ->setFields([
                                             PlaceSearchMapping::NAME_FIELD,
                                             PlaceSearchMapping::NAME_TRANSLIT_FIELD,
                                         ])
                                         ->setType(MultiMatch::TYPE_PHRASE_PREFIX),
            $this->_queryConditionFactory->getFieldQuery(
                [
                    PlaceSearchMapping::NAME_FIELD,
                    PlaceSearchMapping::NAME_TRANSLIT_FIELD,
                ],
                $searchText
            ),
        ]);

        /** добавляем к условию поиска рассчет по совпадению интересов */
        $this->setScriptTagsConditions($currentUser, PlaceSearchMapping::class);

        /** добавляем к условию поиска рассчет расстояния */
        $this->setGeoPointConditions($point, PlaceSearchMapping::class);

        /** устанавливаем фильтры только для мест */
        $this->setFilterQuery(PlaceSearchMapping::getMarkersSearchFilter($this->_queryFilterFactory, $userId));

        $queryMatch = $this->createQuery($skip, $count);

        return $this->searchDocuments($queryMatch, PlaceSearchMapping::CONTEXT);
    }

    /**
     * Метод осуществляет поиск в еластике
     * по имени тип места
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param string|null $searchText Поисковый запрос
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchPlacesTypeByName($userId, $searchText = null, $skip = 0, $count = null)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        /*$queryMatch = $this->createMatchQuery(
            $searchText,
            [
                PlaceTypeSearchMapping::NAME_FIELD,
                PlaceTypeSearchMapping::NAME_TRANSLIT_FIELD
            ],
            $skip, $count
        );*/

        if (!is_null($searchText) && !empty($searchText)) {
            $this->setConditionQueryShould([
                $this->_queryConditionFactory->getDisMaxQuery([
                    $this->_queryConditionFactory->getMultiMatchQuery()
                                                 ->setQuery($searchText)
                                                 ->setFields([
                                                     PlaceTypeSearchMapping::NAME_FIELD,
                                                     PlaceTypeSearchMapping::NAME_TRANSLIT_FIELD,
                                                 ])
                                                 ->setOperator(MultiMatch::OPERATOR_OR)
                                                 ->setType(MultiMatch::TYPE_BEST_FIELDS),
                    $this->_queryConditionFactory->getPrefixQuery(PlaceTypeSearchMapping::NAME_FIELD, $searchText),
                    $this->_queryConditionFactory->getPrefixQuery(PlaceTypeSearchMapping::NAME_TRANSLIT_FIELD, $searchText),

                    $this->_queryConditionFactory->getFieldQuery(PlaceTypeSearchMapping::NAME_WORDS_NAME_FIELD, $searchText),
                    $this->_queryConditionFactory->getFieldQuery(PlaceTypeSearchMapping::NAME_WORDS_TRANSLIT_NAME_FIELD, $searchText),
                ]),
            ]);

            $queryMatch = $this->createQuery($skip, $count);
        } else {
            $queryMatch = $this->createMatchQuery(
                null, [], $skip, $count
            );
        }

        return $this->searchDocuments($queryMatch, PlaceTypeSearchMapping::CONTEXT);
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
    public function searchPlacesByCity(
        $userId,
        $cityId,
        GeoPointServiceInterface $point,
        $searchText = null,
        $skip = 0,
        $count = null
    ) {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        if (!is_null($searchText) && !empty($searchText)) {
            $this->setConditionQueryShould([
                $this->_queryConditionFactory->getMultiMatchQuery()
                                             ->setQuery($searchText)
                                             ->setFields([
                                                 PlaceSearchMapping::NAME_FIELD,
                                                 PlaceSearchMapping::NAME_TRANSLIT_FIELD,
                                             ])
                                             ->setType(MultiMatch::TYPE_PHRASE_PREFIX),
                $this->_queryConditionFactory->getFieldQuery(
                    [
                        PlaceSearchMapping::NAME_FIELD,
                        PlaceSearchMapping::NAME_TRANSLIT_FIELD,
                    ],
                    $searchText
                ),
            ]);
        }

        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermFilter([PlaceSearchMapping::LOCATION_CITY_ID_FIELD => $cityId]),
        ]);

        /** добавляем к условию поиска рассчет по совпадению интересов */
        $this->setScriptTagsConditions($currentUser, PlaceSearchMapping::class);

        /** добавляем к условию поиска рассчет расстояния */
        $this->setGeoPointConditions($point, PlaceSearchMapping::class);

        /** устанавливаем фильтры только для мест */
        $this->setFilterQuery(PlaceSearchMapping::getMarkersSearchFilter($this->_queryFilterFactory, $userId));

        $queryMatch = $this->createQuery($skip, $count);

        return $this->searchDocuments($queryMatch, PlaceSearchMapping::CONTEXT);

    }

    /**
     * Метод осуществляет поиск в еластике
     * скидочных мест
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param GeoPointServiceInterface $point
     * @param array $params параметры запроса
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchPlacesByDiscount(
        $userId,
        GeoPointServiceInterface $point,
        $params = [],
        $skip = 0,
        $count = null
    ) {
        /** получаем текущего ползователя */
        $currentUser = $this->getUserById($userId);

        /** если задан поисковый запрос скидки */
        if (isset($params['search']) && !empty($params['search'])) {
            $this->setConditionQueryShould([
                $this->_queryConditionFactory->getMultiMatchQuery()
                                             ->setQuery($params['search'])
                                             ->setFields(PlaceSearchMapping::getMultiMatchQuerySearchFields()),
                $this->_queryConditionFactory->getWildCardQuery(PlaceSearchMapping::NAME_WORDS_NAME_FIELD, "{$params['search']}*"),
                $this->_queryConditionFactory->getWildCardQuery(PlaceSearchMapping::DESCRIPTION_FIELD, "{$params['search']}*"),
                $this->_queryConditionFactory->getWildCardQuery(PlaceSearchMapping::BONUS_FIELD, "{$params['search']}*"),
            ]);
        }

        if (isset($params['countryId']) && !empty($params['countryId'])) {
            $this->setConditionQueryMust([
                $this->_queryConditionFactory->getTermQuery(PlaceSearchMapping::LOCATION_COUNTRY_ID_FIELD, $params['countryId']),
            ]);
        }

        if (isset($params['cityId']) && !empty($params['cityId'])) {
            $this->setConditionQueryMust([
                $this->_queryConditionFactory->getTermQuery(PlaceSearchMapping::LOCATION_CITY_ID_FIELD, $params['cityId']),
            ]);
        }

        $this->setFilterDiscounts($userId);

        /** добавляем к условию поиска рассчет по совпадению интересов */
        $this->setScriptTagsConditions($currentUser, PlaceSearchMapping::class);

        /** добавляем к условию поиска рассчет расстояния */
        $this->setGeoPointConditions($point, PlaceSearchMapping::class);

        $this->setHighlightQuery([
            'description' => [],
        ]);

        $queryMatch = $this->createQuery($skip, $count);

        return $this->searchDocuments($queryMatch, PlaceSearchMapping::CONTEXT);
    }

    /**
     * Метод осуществляет поиск в еластике
     * скидочных мест (для метода promo)
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param GeoPointServiceInterface $point
     * @param int|null $skip Кол-во пропускаемых позиций поискового результата
     * @param int|null $count Какое кол-во выводим
     * @param int|null $countryId ID страны
     * @param int|null $cityId ID города
     * @return array Массив с найденными результатами
     */
    public function searchPromoPlaces($userId, GeoPointServiceInterface $point, $skip = null, $count = null, $countryId = null, $cityId = null)
    {
        $currentUser = $this->getUserById($userId);

        $userPoint = $currentUser->getLocation();

        if ((!$point->isValid() || $point->isEmpty()) && ($userPoint->isValid() && !$userPoint->isEmpty())) {
            $point = $userPoint;
        }

        $aggr = $this->_queryAggregationFactory;

        /** @var QueryScriptFactory $scriptFactory */
        $scriptFactory = $this->_scriptFactory;

        $script_fields = [
            'tagsInPercent'     => $scriptFactory->getTagsIntersectInPercentScript(
                $this->filterTypes[PlaceSearchMapping::CONTEXT]::TAGS_ID_FIELD,
                $currentUser->getTags()
            ),
            'tagsCount'         => $scriptFactory->getTagsIntersectScript(
                $this->filterTypes[PlaceSearchMapping::CONTEXT]::TAGS_ID_FIELD,
                $currentUser->getTags()
            ),
            'distance'          => $scriptFactory->getDistanceScript(
                $this->filterTypes[PlaceSearchMapping::CONTEXT]::LOCATION_POINT_FIELD,
                $point
            ),
            'distanceInPercent' => $scriptFactory->getDistanceInPercentScript(
                $this->filterTypes[PlaceSearchMapping::CONTEXT]::LOCATION_POINT_FIELD,
                $point
            ),
        ];

        if (!empty($countryId)) {
            $this->setAggregationQuery([
                $aggr
                    ->getTermsAggregation(PlaceSearchMapping::LOCATION_COUNTRY_ID_FIELD, null, '_count', 'desc')
                    ->addAggregation($aggr
                        ->setAggregationSource(PlaceSearchMapping::CONTEXT, [], $count)
                    )
                    ->addAggregation($aggr
                        ->getTermsAggregation(PlaceSearchMapping::LOCATION_CITY_ID_FIELD, null, '_count', 'desc')
                        ->addAggregation($aggr
                            ->setAggregationSource(PlaceSearchMapping::CONTEXT, [], $count, $script_fields)
                            ->setSort([PlaceSearchMapping::PLACE_ID_FIELD => SortingOrder::SORTING_DESC])
                            ->setFrom($skip)
                        )
                    ),
            ]);
        } else {
            $this->setAggregationQuery([
                $aggr
                    ->getTermsAggregation(PlaceSearchMapping::LOCATION_COUNTRY_ID_FIELD, null, '_count', 'desc')
                    ->addAggregation($aggr
                        ->setAggregationSource(PlaceSearchMapping::CONTEXT, [], $count, $script_fields)
                    ),
            ]);
        }

        $this->setFilterDiscounts($userId);

        /** добавляем к условию поиска рассчет по совпадению интересов */
        $this->setScriptTagsConditions($currentUser, PlaceSearchMapping::class);

        /** добавляем к условию поиска рассчет расстояния */
        $this->setGeoPointConditions($point, PlaceSearchMapping::class);

        $queryMatch = $this->createMatchQuery(null, PlaceSearchMapping::getMultiMatchQuerySearchFields());

        $this->searchDocuments($queryMatch, PlaceSearchMapping::CONTEXT);

        $places = $this->getAggregations();

        return BackwardCompatibilityHelper::preparePromoPlaces($places, $countryId, $cityId);
    }

    /**
     * Установка фильтров для поиска только скидок
     * которые так же прошли модерацию (т.е. не удалены)
     *
     * @param string $userId ID пользователя (автор места)
     * @return void
     */
    private function setFilterDiscounts($userId)
    {
        $this->setFilterQuery([
            $this->_queryFilterFactory->getBoolOrFilter([
                $this->_queryFilterFactory->getGtFilter(PlaceSearchMapping::DISCOUNT_FIELD, 0),
                $this->_queryFilterFactory->getExistsFilter(PlaceSearchMapping::BONUS_FIELD),
            ]),
            $this->_queryFilterFactory->getBoolOrFilter([
                $this->_queryFilterFactory->getBoolAndFilter([
                    $this->_queryFilterFactory->getNotFilter(
                        $this->_queryFilterFactory->getTermFilter([PlaceSearchMapping::AUTHOR_ID_FIELD => $userId])
                    ),
                    $this->_queryFilterFactory->getTermFilter([
                        PlaceSearchMapping::MODERATION_STATUS_FIELD => ModerationStatus::OK,
                    ]),
                ]),
                $this->_queryFilterFactory->getBoolAndFilter([
                    $this->_queryFilterFactory->getTermFilter([PlaceSearchMapping::AUTHOR_ID_FIELD => $userId]),
                    $this->_queryFilterFactory->getTermsFilter(PlaceSearchMapping::MODERATION_STATUS_FIELD, [
                        ModerationStatus::DIRTY,
                        ModerationStatus::OK,
                    ]),
                ]),
            ]),
        ]);
    }

    /**
     * Установка фильтров для поиска только мест
     * без бонусов и скидок только места
     * которые так же прошли модерацию (т.е. не удалены)
     *
     * @param string|null $userId ID пользователя (автор места)
     * @return void
     */
    private function setFilterPlaces($userId = null)
    {
        $this->setFilterQuery([
            $this->_queryFilterFactory->getNotFilter(
                $this->_queryFilterFactory->getTermFilter([PlaceSearchMapping::MODERATION_STATUS_FIELD => ModerationStatus::DELETED])
            ),
            $this->_queryFilterFactory->getTermFilter([PlaceSearchMapping::REMOVED_FIELD => false]),
            $this->_queryFilterFactory->getBoolAndFilter([
                $this->_queryFilterFactory->getNotFilter(
                    $this->_queryFilterFactory->getExistsFilter(PlaceSearchMapping::BONUS_FIELD)
                ),
                $this->_queryFilterFactory->getTermFilter([PlaceSearchMapping::DISCOUNT_FIELD => 0]),
            ]),
        ]);
    }

    /**
     * Поиск места по его ID с учетом фильтров
     *
     * @param string $userId ID автора места
     * @param string $context Контекст поиска
     * @param string $fieldId Название поля идентификатора
     * @param string $recordId ID записи места которое надо найти
     * @param GeoPointServiceInterface $point
     * @return array Набор данных найденной записи
     */
    public function getPlaceById($userId, $context, $fieldId, $recordId, GeoPointServiceInterface $point)
    {
        $filter = $this->_queryFilterFactory;

        if (empty($userId)) {
            $moderate = $filter->getTermFilter([PlaceSearchMapping::MODERATION_STATUS_FIELD => ModerationStatus::OK]);

            $discount = $filter->getBoolOrFilter([
                $filter->getBoolAndFilter([
                    $moderate,
                    $filter->getBoolOrFilter([
                        $filter->getGtFilter(PlaceSearchMapping::DISCOUNT_FIELD, 0),
                        $filter->getExistsFilter(PlaceSearchMapping::BONUS_FIELD),
                    ]),
                ]),
                $filter->getBoolAndFilter([
                    $filter->getTermFilter([PlaceSearchMapping::DISCOUNT_FIELD => 0]),
                    $filter->getNotFilter(
                        $filter->getExistsFilter(PlaceSearchMapping::BONUS_FIELD)
                    ),
                ]),
            ]);

            $visible = $filter->getTermFilter([PlaceSearchMapping::VISIBLE_FIELD => Visible::ALL]);

            $this->setFilterQuery([
                $filter->getBoolAndFilter([$discount, $visible]),
            ]);
        } else {

            /** получаем текущего ползователя */
            $currentUser = $this->getUserById($userId);

            $moderate = $filter->getBoolOrFilter([
                $filter->getTermFilter([PlaceSearchMapping::MODERATION_STATUS_FIELD => ModerationStatus::OK]),
                $filter->getBoolAndFilter([
                    $filter->getTermFilter([PlaceSearchMapping::AUTHOR_ID_FIELD => $userId]),
                    $filter->getTermsFilter(PlaceSearchMapping::MODERATION_STATUS_FIELD, [
                        ModerationStatus::DIRTY,
                        ModerationStatus::REJECTED,
                        ModerationStatus::RESTORED,
                    ]),
                ]),
            ]);

            $discount = $filter->getBoolOrFilter([
                $filter->getBoolAndFilter([
                    $moderate,
                    $filter->getBoolOrFilter([
                        $filter->getGtFilter(PlaceSearchMapping::DISCOUNT_FIELD, 0),
                        $filter->getExistsFilter(PlaceSearchMapping::BONUS_FIELD),
                    ]),
                ]),
                $filter->getBoolAndFilter([
                    $filter->getTermFilter([PlaceSearchMapping::DISCOUNT_FIELD => 0]),
                    $filter->getNotFilter(
                        $filter->getExistsFilter(PlaceSearchMapping::BONUS_FIELD)
                    ),
                ]),
            ]);

            $visible = $filter->getBoolOrFilter([
                $filter->getTermFilter([PlaceSearchMapping::AUTHOR_ID_FIELD => $userId]),
                $filter->getTermFilter([PlaceSearchMapping::VISIBLE_FIELD => Visible::ALL]),
                $filter->getBoolAndFilter([
                    $filter->getTermsFilter(PlaceSearchMapping::AUTHOR_FRIENDS_FIELD, [$userId]),
                    $filter->getTermsFilter(PlaceSearchMapping::VISIBLE_FIELD, [Visible::FRIEND]),
                ]),
                $filter->getBoolAndFilter([
                    $filter->getNotFilter(
                        $filter->getTermsFilter(PlaceSearchMapping::AUTHOR_FRIENDS_FIELD, [$userId])
                    ),
                    $filter->getTermsFilter(PlaceSearchMapping::VISIBLE_FIELD, [Visible::NOT_FRIEND]),
                ]),
            ]);

            $this->setFilterQuery([
                $filter->getBoolAndFilter([$discount, $visible]),
            ]);

            /** добавляем к условию поиска рассчет по совпадению интересов */
            $this->setScriptTagsConditions($currentUser, PlaceSearchMapping::class);
        }

        /** добавляем к условию поиска рассчет расстояния */
        $this->setGeoPointConditions($point, PlaceSearchMapping::class);

        return $this->searchRecordById($context, $fieldId, $recordId);
    }

    /**
     * Поиск скидки по его ID с учетом фильтров
     *
     * @param string $userId ID автора места
     * @param string $context Контекст поиска
     * @param string $fieldId Название поля идентификатора
     * @param string $recordId ID записи места которое надо найти
     * @return array Набор данных найденной записи
     */
    public function getDiscountById($userId, $context, $fieldId, $recordId)
    {
        $this->setFilterDiscounts($userId);

        return $this->searchRecordById($context, $fieldId, $recordId);
    }

    /**
     * Поиск типа места по его ID
     *
     * @param string $userId ID автора места
     * @param string $context Контекст поиска
     * @param string $fieldId Название поля идентификатора
     * @param string $recordId ID записи места которое надо найти
     * @return array Набор данных найденной записи
     */
    public function getPlaceTypeById($userId, $context, $fieldId, $recordId)
    {
        return $this->searchRecordById($context, $fieldId, $recordId);
    }
}
