<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 26.10.16
 * Time: 16:13
 */

namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryCondition\ConditionFactoryInterface;
use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;
use Elastica\Query\MultiMatch;

class HelpOffersSearchMapping extends PeopleSearchMapping
{
    /** Контекст маркера */
    const CONTEXT = 'helpOffers';

    /** Контекст поиска */
    const CONTEXT_MARKER = 'help';

    /** "Могу помочь" */
    const HELP_OFFERS_LIST_FIELD = 'helpOffers';

    const HELP_OFFERS_ID_FIELD = 'helpOffers.id';

    const HELP_OFFERS_NAME_FIELD = 'helpOffers.name';

    const HELP_OFFERS_NAME_TRANSLIT_FIELD = 'helpOffers._translit';

    const HELP_OFFERS_NAME_TRANSLIT_NGRAM_FIELD = 'helpOffers._translitNgram';

    const HELP_OFFERS_NAME_NGRAM_FIELD = 'helpOffers._nameNgram';

    const HELP_OFFERS_NAME_WORDS_NAME_FIELD = 'helpOffers._wordsName';

    /**
     * Получаем поля для поиска
     * сбор полей для формирования объекта запроса
     * multiMatch - без точных условий с возможностью фильтрации
     *
     * @return array
     */
    public static function getMultiMatchQuerySearchFields()
    {
        return [
            self::HELP_OFFERS_NAME_FIELD,
            //self::HELP_OFFERS_NAME_NGRAM_FIELD,
            self::HELP_OFFERS_NAME_TRANSLIT_FIELD,
            //self::HELP_OFFERS_NAME_TRANSLIT_NGRAM_FIELD,
        ];
    }

    /**
     * Метод собирает условие построенные для глобального поиска
     * обязательное условие при запросе
     *
     * @param ConditionFactoryInterface $conditionFactory Объект класса билдера условий
     * @param string $queryString Строка запроса
     * @return array
     */
    public static function getSearchConditionQueryMust(ConditionFactoryInterface $conditionFactory, $queryString)
    {
        return [
            $conditionFactory
                ->getFieldQuery(self::getMultiMatchQuerySearchFields(), $queryString)
                ->setDefaultOperator(MultiMatch::OPERATOR_AND),
        ];
    }


    /**
     * Метод собирает условие построенные для глобального поиска
     * может попасть или может учитыватся при выборке
     *
     * @param ConditionFactoryInterface $conditionFactory Объект класса билдера условий
     * @param string $queryString Строка запроса
     * @return array
     */
    public static function getSearchConditionQueryShould(ConditionFactoryInterface $conditionFactory, $queryString)
    {
        $should = [];
        $allSearchFields = self::getMultiMatchQuerySearchFields();

        foreach ($allSearchFields as $field) {
            $should[] = $conditionFactory->getMatchPhrasePrefixQuery($field, $queryString);
        }

        return [
            $conditionFactory->getMultiMatchQuery()
                             ->setFields(self::getMultiMatchQuerySearchFields())
                             ->setQuery($queryString),
            $conditionFactory->getBoolQuery([], $should, []),
        ];
    }

    /**
     * Собираем фильтр для маркеров
     *
     * @param \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return array
     */
    public static function getMarkersSearchFilter(FilterFactoryInterface $filterFactory, $userId = null)
    {
        return [
            $filterFactory->getExistsFilter(self::HELP_OFFERS_LIST_FIELD)
        ];
    }

    /**
     * Собираем фильтр для поиска
     *
     * @param \Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface $filterFactory Объект фильтрации
     * @param string|null $userId ID пользователя (не обязательный параметр для всех фильтров)
     * @return array
     */
    public static function getMatchSearchFilter(FilterFactoryInterface $filterFactory, $userId = null)
    {
        return [
            $filterFactory->getExistsFilter(self::HELP_OFFERS_LIST_FIELD)
        ];
    }

    /**
     * Статический класс получения условий подсветки при поиске
     * @return array
     */
    public static function getHighlightConditions()
    {
        $highlight[self::HELP_OFFERS_NAME_FIELD] = [
            'term_vector' => 'with_positions_offsets'
        ];

        return $highlight;
    }
}