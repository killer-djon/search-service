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
                                         ->setFields(PlaceSearchMapping::getMultiMatchQuerySearchFields()),
            $this->_queryConditionFactory->getWildCardQuery(
                PlaceSearchMapping::DESCRIPTION_FIELD,
                $searchText
            ),
            $this->_queryConditionFactory->getWildCardQuery(
                PlaceSearchMapping::NAME_WORDS_NAME_FIELD,
                $searchText
            ),
            $this->_queryConditionFactory->getWildCardQuery(
                PlaceSearchMapping::TYPE_WORDS_FIELD,
                $searchText
            ),
            $this->_queryConditionFactory->getWildCardQuery(
                PlaceSearchMapping::TAG_WORDS_FIELD,
                $searchText
            ),
        ]);

        /** добавляем к условию поиска рассчет по совпадению интересов */
        $this->setScriptTagsConditions($currentUser, PlaceSearchMapping::class);

        /** добавляем к условию поиска рассчет расстояния */
        $this->setGeoPointConditions($point, PlaceSearchMapping::class);

        /** устанавливаем фильтры только для мест */
        $this->setFilterPlaces($userId);

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

        $queryMatch = $this->createMatchQuery(
            $searchText,
            [
                PlaceTypeSearchMapping::NAME_FIELD,
                PlaceTypeSearchMapping::NAME_NGRAM_FIELD,
                PlaceTypeSearchMapping::NAME_TRANSLIT_FIELD,
                PlaceTypeSearchMapping::NAME_TRANSLIT_NGRAM_FIELD
            ],
            $skip, $count
        );

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
    public function searchPlacesByCity($userId, $cityId, GeoPointServiceInterface $point, $searchText = null, $skip = 0, $count = null)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        if (!is_null($searchText) && !empty($searchText)) {
            $this->setConditionQueryShould([
                $this->_queryConditionFactory->getMultiMatchQuery()
                                             ->setQuery($searchText)
                                             ->setFields(PlaceSearchMapping::getMultiMatchQuerySearchFields()),
                $this->_queryConditionFactory->getWildCardQuery(
                    PlaceSearchMapping::DESCRIPTION_FIELD,
                    $searchText
                ),
                $this->_queryConditionFactory->getWildCardQuery(
                    PlaceSearchMapping::NAME_WORDS_NAME_FIELD,
                    $searchText
                ),
                $this->_queryConditionFactory->getWildCardQuery(
                    PlaceSearchMapping::TYPE_WORDS_FIELD,
                    $searchText
                ),
                $this->_queryConditionFactory->getWildCardQuery(
                    PlaceSearchMapping::TAG_WORDS_FIELD,
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
        $this->setFilterPlaces($userId);

        $queryMatch = $this->createQuery($skip, $count);

        return $this->searchDocuments($queryMatch, PlaceSearchMapping::CONTEXT);

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
    public function searchPlacesByDiscount($userId, GeoPointServiceInterface $point, $searchText = null, $cityId = null, $skip = 0, $count = null)
    {
        /** получаем текущего ползователя */
        $currentUser = $this->getUserById($userId);

        /** если задан поисковый запрос скидки */
        if (!is_null($searchText) && !empty($searchText)) {
            $this->setConditionQueryShould([
                $this->_queryConditionFactory->getMultiMatchQuery()
                                             ->setQuery($searchText)
                                             ->setFields(PlaceSearchMapping::getMultiMatchQuerySearchFields()),
                $this->_queryConditionFactory->getWildCardQuery(
                    PlaceSearchMapping::NAME_WORDS_NAME_FIELD,
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

        /** если вдруг захотим искать скидки по городу */
        if (!is_null($cityId) && !empty($cityId)) {
            $this->setConditionQueryMust([
                $this->_queryConditionFactory->getTermQuery(PlaceSearchMapping::LOCATION_CITY_ID_FIELD, $cityId),
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
                $this->_queryFilterFactory->getRangeFilter(PlaceSearchMapping::DISCOUNT_FIELD, 1, 100),
                $this->_queryFilterFactory->getExistsFilter(PlaceSearchMapping::BONUS_FIELD),
            ]),
            $this->_queryFilterFactory->getBoolOrFilter([
                $this->_queryFilterFactory->getBoolAndFilter([
                    $this->_queryFilterFactory->getNotFilter(
                        $this->_queryFilterFactory->getTermFilter([PlaceSearchMapping::AUTHOR_ID_FIELD => $userId])
                    ),
                    $this->_queryFilterFactory->getTermFilter([
                        PlaceSearchMapping::MODERATION_STATUS_FIELD => ModerationStatus::OK
                    ])
                ]),
                $this->_queryFilterFactory->getBoolAndFilter([
                    $this->_queryFilterFactory->getTermFilter([PlaceSearchMapping::AUTHOR_ID_FIELD => $userId]),
                    $this->_queryFilterFactory->getTermsFilter(PlaceSearchMapping::MODERATION_STATUS_FIELD, [
                        ModerationStatus::DIRTY,
                        ModerationStatus::OK
                    ]),
                ])
            ])
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
            $this->_queryFilterFactory->getBoolAndFilter([
                $this->_queryFilterFactory->getNotFilter(
                    $this->_queryFilterFactory->getExistsFilter(PlaceSearchMapping::BONUS_FIELD)
                ),
                $this->_queryFilterFactory->getTermFilter([PlaceSearchMapping::DISCOUNT_FIELD => 0]),
            ])
        ]);
    }

    /**
     * Поиск места по его ID с учетом фильтров
     *
     * @param string $userId ID автора места
     * @param string $context Контекст поиска
     * @param string $fieldId Название поля идентификатора
     * @param string $recordId ID записи места которое надо найти
     * @return array Набор данных найденной записи
     */
    public function getPlaceById($userId, $context, $fieldId, $recordId)
    {
        $this->setFilterPlaces($userId);

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