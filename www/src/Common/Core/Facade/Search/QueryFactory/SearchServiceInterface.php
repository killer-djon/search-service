<?php
/**
 * Интерфейс формирующий поведение поисковых сервисов
 */
namespace Common\Core\Facade\Search\QueryFactory;

interface SearchServiceInterface
{
    /**
     * Метод который формирует условия запроса
     *
     * @param array $must Массив условий запроса $must
     * @return SearchServiceInterface
     */
    public function setConditionQueryMust(array $must = []);

    /**
     * Метод который формирует условия запроса
     *
     * @param array $mustNot Массив условий запроса $mustNot
     * @return SearchServiceInterface
     */
    public function setConditionQueryMustNot(array $mustNot = []);

    /**
     * Метод который формирует условия запроса
     *
     * @param array $should Массив условий запроса $should
     * @return SearchServiceInterface
     */
    public function setConditionQueryShould(array $should = []);

    /**
     * Метод который формирует набор фильтров для запроса
     *
     * @param array $filters Массив фильтров
     * @return SearchServiceInterface
     */
    public function setFilterQuery(array $filters = []);

    /**
     * Создаем аггрегированные условия запроса
     * так называемый aggregation (например суммирование результата по условию)
     * как аггрегированные функции
     *
     * @param array $aggregations Набор аггрегированных функций
     * @return SearchServiceInterface
     */
    public function setAggregationQuery(array $aggregations = []);

    /**
     * Формируем условие сортировки
     *
     * @param array $sortings Массив c условиями сортировки данных
     * @return SearchServiceInterface
     */
    public function setSortingQuery(array $sortings = []);

    /**
     * Метод который собирает в один запрос все условия
     *
     * @param int $skip
     * @param int $count
     * @return \Elastica\Query
     */
    public function createQuery($skip = 0, $count = null);

    /**
     * Создание объект поиска на основе совпадения по полям
     *
     * @param string $searchText Текст поиска
     * @param array $fields Набор полей учавствующих в поиске
     * @param int $skip
     * @param int $count
     * @param string $operator Оператор логического выражения ( or and )
     * @param string $type Тип перебора полей в поиске
     * @return \Elastica\Query
     */
    public function createMatchQuery(
        $searchText,
        array $fields,
        $skip = 0,
        $count = null,
        $operator = \Elastica\Query\MultiMatch::OPERATOR_OR,
        $type = \Elastica\Query\MultiMatch::TYPE_CROSS_FIELDS
    );
}