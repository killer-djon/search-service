<?php
/**
 * Сервис поиска по условиям городов
 */

namespace RP\SearchBundle\Services;

use Common\Core\Constants\SortingOrder;
use Common\Core\Constants\Visible;
use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use Common\Core\Facade\Service\User\UserProfileService;
use Elastica\Exception\ElasticsearchException;
use Common\Core\Facade\Service\Geo\GeoPointService;
use RP\SearchBundle\Services\Mapping\CitySearchMapping;

class CitySearchService extends AbstractSearchService
{
    /**
     * Метод осуществляет поиск в еластике
     * по названию города
     *
     * @param string $userId ID пользователя который делает запрос к АПИ
     * @param string $searchText Поисковый запрос
     * @param GeoPointServiceInterface $point
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchCityByName($userId, $searchText, GeoPointServiceInterface $point, $skip = 0, $count = null)
    {
        /** получаем объект текущего пользователя */
        $currentUser = $this->getUserById($userId);

        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermsFilter(CitySearchMapping::CITY_TYPE_FIELD, [
                'city'
            ])
        ]);

        /** Получаем сформированный объект запроса */
        $queryMatchResult = $this->createMatchQuery(
            $searchText,
            CitySearchMapping::getMultiMatchQuerySearchFields(),
            $skip,
            $count
        );

        /** поиск документа */
        return $this->searchDocuments($queryMatchResult, CitySearchMapping::CONTEXT);
    }
}