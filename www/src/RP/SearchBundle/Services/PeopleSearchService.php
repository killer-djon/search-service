<?php
/**
 * Сервис поиска в коллекции пользователей
 */
namespace RP\SearchBundle\Services;

use Common\Core\Constants\Visible;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;

class PeopleSearchService extends AbstractSearchService
{

    /**
     * Метод осуществляет поиск в еластике
     * по имени/фамилии пользьвателя
     *
     * @param string $userId ID пользователя который посылает запрос
     * @param string $username Часть искомой строки поиска
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     *
     * @return array Массив с найденными результатами
     */
    public function searchPeopleByUserName($userId, $username, $skip = 0, $count = null)
    {
        $this->setConditionQueryShould([
            $this->_queryConditionFactory->getFuzzyQuery(PeopleSearchMapping::NAME_PREFIX_FIELD, $username),
            $this->_queryConditionFactory->getFuzzyQuery(PeopleSearchMapping::SURNAME_PREFIX_FIELD, $username),
            /*$this->_queryConditionFactory->getMatchQuery(PeopleSearchMapping::NAME_FIELD, $username),
            $this->_queryConditionFactory->getMatchQuery(PeopleSearchMapping::NAME_TRANSLIT_FIELD, $username),
            $this->_queryConditionFactory->getMatchQuery(PeopleSearchMapping::SURNAME_FIELD, $username),
            $this->_queryConditionFactory->getMatchQuery(PeopleSearchMapping::SURNAME_TRANSLIT_FIELD, $username)*/

        ]);
        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermsFilter(PeopleSearchMapping::FRIEND_LIST_FIELD, [$userId])
        ]);

        /** Получаем сформированный объект запроса */
        $queryResult = $this->createQuery($skip, $count);

        return $this->searchDocuments(PeopleSearchMapping::CONTEXT, $queryResult);
    }

}