<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 26.10.16
 * Time: 16:13
 */

namespace RP\SearchBundle\Services\Mapping;

use Common\Core\Facade\Search\QueryFilter\FilterFactoryInterface;

class HelpOffersSearchMapping extends PeopleSearchMapping
{
    /** Контекст маркера */
    const CONTEXT = 'helpOffers';

    /** Контекст поиска */
    const CONTEXT_MARKER = 'help';

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