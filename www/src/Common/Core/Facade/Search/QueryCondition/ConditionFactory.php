<?php
/**
 * Класс отвечающий за генерацию условий поиска
 */
namespace Common\Core\Facade\Search\QueryCondition;

use Elastica\Filter\AbstractFilter;
use Elastica\Query\AbstractQuery;

class ConditionFactory implements ConditionFactoryInterface
{
    /**
     * Условие запроса поиска всех документов
     *
     * @link http://www.elasticsearch.org/guide/reference/query-dsl/match-all-query.html
     * @return \Elastica\Query\MatchAll
     */
    public function getMatchAllQuery()
    {
        return new \Elastica\Query\MatchAll();
    }

    /**
     * Условие запроса поиска по похожести текста
     *
     * @param string $textToSearch
     * @param array $fields
     * @param null|float $minSimilarity
     * @return \Elastica\Query\FuzzyLikeThisCondition
     */
    public function getFuzzyLikeThisQuery($textToSearch, array $fields, $minSimilarity = self::MIN_SIMILARITY)
    {
        $fuzzyLike = new \Elastica\Query\FuzzyLikeThis();
        $fuzzyLike->addFields($fields);
        $fuzzyLike->setLikeText($textToSearch);
        $fuzzyLike->setMinSimilarity($minSimilarity);

        return $fuzzyLike;
    }

    /**
     * Условие префиксного запроса
     *
     * @param string $fieldName
     * @param string $value
     * @return \Elastica\Query\PrefixCondition
     */
    public function getPrefixQuery($fieldName, $value, $boost = 1.0)
    {
        return new \Elastica\Query\Prefix([
            $fieldName => [
                'value' => $value,
                'boost' => $boost,
            ],
        ]);
    }

    /**
     * Условие в виде логических комбинаций других условий
     *
     * @param array $must
     * @param array $should
     * @param array $mustNot
     * @param float|null $boost
     * @return \Elastica\Query\Bool
     */
    public function getBoolQuery(array $musts, array $shoulds, array $mustNots, $boost = null)
    {
        $boolQuery = new \Elastica\Query\BoolQuery();
        if (!empty($musts)) {
            foreach ($musts as $must) {
                $boolQuery->addMust($must);
            }
        }

        if (!empty($shoulds)) {
            foreach ($shoulds as $should) {
                $boolQuery->addShould($should);
            }
        }

        if (!empty($mustNots)) {
            foreach ($mustNots as $mustNot) {
                $boolQuery->addMustNot($mustNot);
            }
        }

        if (!is_null($boost)) {
            $boolQuery->setBoost($boost);
        }

        return $boolQuery;
    }

    /**
     * Условие расчета кастомного значения очков релевантности результатов поиска
     *
     * @param QueryConditionInterface $query
     * @param string $script
     * @param \Elastica\Filter\AbstractFilter $filter
     * @param array $params
     * @param string $langScript
     * @return \Elastica\Query\FunctionScore
     */
    public function getCustomScoreQuery(AbstractQuery $query, $script, AbstractFilter $filter = null, array $params = [], $lang = \Elastica\Script::LANG_GROOVY)
    {
        $customScore = new \Elastica\Query\FunctionScore();
        $customScore->setQuery($query);

        $script = new \Elastica\Script($script, $params, \Elastica\Script::LANG_GROOVY);
        $customScore->addScriptScoreFunction($script);

        return $customScore;
    }

    /**
     * Условие точного совпадения запроса по заданному терму
     *
     * @param string $fieldName
     * @param string $value
     * @return \Elastica\Query\TermCondition
     */
    public function getTermQuery($fieldName, $value)
    {
        return new \Elastica\Query\Term([
            $fieldName => $value,
        ]);
    }

    /**
     * Условие совпадения запроса с перечисленным списком термов
     *
     * @param string $fieldName
     * @param array $values
     * @return \Elastica\Query\TermsCondition
     */
    public function getTermsQuery($fieldName, array $values)
    {
        return new \Elastica\Query\Terms($fieldName, $values);
    }

    /**
     * Условие запроса по ids
     *
     * @param array $ids
     * @return \Elastica\Query\IdsCondition
     */
    public function getIdsQuery(array $ids)
    {
        return new \Elastica\Query\Ids(null, $ids);
    }

    /**
     * Условаие запроса по совпадению поля (в зависимости от анализатора конкретного поля).
     *
     * @param string $fieldName
     * @param string $value
     * @return mixed
     */
    public function getFieldQuery($fieldName, $value)
    {
        $queryString = new \Elastica\Query\QueryString($value);
        $queryString->setDefaultField($fieldName);

        return $queryString;
    }

}