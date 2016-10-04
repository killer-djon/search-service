<?php
/**
 * Сервис поиска в коллекции пользователей
 */
namespace RP\SearchBundle\Services;

use Common\Core\Constants\Visible;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;

class PeopleSearchService extends AbstractSearchService
{
    public function searchPeopleByFilter($skip = 0, $count = null)
    {
        $this->setConditionQueryMust([
            $this->_queryConditionFactory->getFieldQuery(PeopleSearchMapping::AUTOCOMPLETE_ID_PARAM, '3590'),
            $this->_queryConditionFactory->getTermsQuery(PeopleSearchMapping::LOCATION_VISIBILITY_FIELD, [
                Visible::ALL,
                Visible::FRIEND,
            ]),
        ]);

        $this->setFilterQuery([
            $this->_queryFilterFactory->getNotFilter(
                $this->_queryFilterFactory->getTermFilter([
                    PeopleSearchMapping::USER_REMOVED_FIELD => true,
                ])
            ),
        ]);

        /** Получаем сформированный объект запроса */
        $queryResult = $this->createQuery($skip, $count);

        return $this->searchDocuments(PeopleSearchMapping::CONTEXT, $queryResult);
    }
}