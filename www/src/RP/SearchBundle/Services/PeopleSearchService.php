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
     * Минимальное значение скора (вес найденного результата)
     *
     * @const string MIN_SCORE_SEARCH
     */
    const MIN_SCORE_SEARCH = '3';

    /**
     * Метод осуществляет поиск в еластике
     * по имени/фамилии пользьвателя
     *
     * @param string $userId ID пользователя который посылает запрос
     * @param string $username Часть искомой строки поиска
     * @param int $skip Кол-во пропускаемых позиций поискового результата
     * @param int $count Какое кол-во выводим
     * @return array Массив с найденными результатами
     */
    public function searchPeopleByUserName($userId, $username, $skip = 0, $count = null)
    {

        $this->setFilterQuery([
            $this->_queryFilterFactory->getTypeFilter(PeopleSearchMapping::CONTEXT),
        ]);

        $scriptBuilder = new CustomScoreBuilderScript();
        $script = $scriptBuilder->getScript();
        //$this->setScriptFunction($script); // @todo надо разобраться почему не хочет скрипт рабоать

        /** Получаем сформированный объект запроса */
        $queryMatchResult = $this->createMatchQuery(
            $username,
            $this->getMatchQueryFields()
        );

        $queryMatchResult->setMinScore(self::MIN_SCORE_SEARCH);
        return $this->searchDocuments(PeopleSearchMapping::CONTEXT, $queryMatchResult);
    }

    /**
     * Возвращаем набор полей для поиска по совпадению
     *
     * @return array $fields
     */
    private function getMatchQueryFields()
    {
        return [
            // вариации поля имени
            PeopleSearchMapping::NAME_FIELD,
            PeopleSearchMapping::NAME_NGRAM_FIELD,
            PeopleSearchMapping::NAME_TRANSLIT_FIELD,
            PeopleSearchMapping::NAME_TRANSLIT_NGRAM_FIELD,
            // вариации поля фамилии
            PeopleSearchMapping::SURNAME_FIELD,
            PeopleSearchMapping::SURNAME_NGRAM_FIELD,
            PeopleSearchMapping::SURNAME_TRANSLIT_FIELD,
            PeopleSearchMapping::SURNAME_TRANSLIT_NGRAM_FIELD,
        ];
    }
}