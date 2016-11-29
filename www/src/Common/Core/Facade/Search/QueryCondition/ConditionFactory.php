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
     * Условие запроса поиска всех документов
     *
     * @param string $fieldName
     * @param string $queryString
     * @param float $boost
     * @param int $prefixLength
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html
     * @return \Elastica\Query\Match
     */
    public function getMatchQuery($fieldName, $queryString, $boost = 1.0, $prefixLength = null)
    {
        $matchQuery = new \Elastica\Query\Match();
        $matchQuery->setFieldQuery($fieldName, $queryString);
        $matchQuery->setFieldBoost($fieldName, $boost);
        if( !is_null($prefixLength) && is_int($prefixLength) )
        {
            $matchQuery->setFieldPrefixLength($fieldName, $prefixLength);
        }

        return $matchQuery;
    }

    /**
     * Условие запроса по полной фразе
     *
     * @param string $path
     * @param AbstractQuery $queryString
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-nested-query.html
     * @return \Elastica\Query\Nested
     */
    public function getNestedQuery($path, AbstractQuery $queryString = null){

        if(is_null($queryString))
        {
            $queryString = $this->getMatchAllQuery();
        }

        $nestedQuery = new \Elastica\Query\Nested();
        $nestedQuery->setPath($path);
        $nestedQuery->setQuery($queryString);

        return $nestedQuery;
    }


    /**
     * Условие запроса по полной фразе
     *
     * @param string $fieldName
     * @param string $queryString
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html#_phrase
     * @return \Elastica\Query\MatchPhrase
     */
    public function getMatchPhraseQuery($fieldName, $queryString)
    {
        $matchPhrase = new \Elastica\Query\MatchPhrase();
        $matchPhrase->setFieldQuery($fieldName, $queryString);
        //$matchPhrase->setFieldPrefixLength($fieldName, mb_strlen($queryString));

        return $matchPhrase;
    }

    /**
     * Получаем объект запроса поиска по совпадению в полях
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html
     * @return \Elastica\Query\MultiMatch
     */
    public function getMultiMatchQuery()
    {
        return new \Elastica\Query\MultiMatch();
    }

    /**
     * Условие запроса поиска по похожести текста
     *
     * @param string $fieldName
     * @param string $textToSearch
     * @return \Elastica\Query\Fuzzy
     */
    public function getFuzzyQuery($fieldName, $textToSearch)
    {
        $fuzzyLike = new \Elastica\Query\Fuzzy();
        $fuzzyLike->setField($fieldName, $textToSearch);

        return $fuzzyLike;
    }

    /**
     * Условие префиксного запроса
     *
     * @param string $fieldName
     * @param string $value
     * @return \Elastica\Query\Prefix
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
    public function getCustomScoreQuery(
        AbstractQuery $query,
        $script,
        AbstractFilter $filter = null,
        array $params = [],
        $lang = \Elastica\Script::LANG_GROOVY
    ) {
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
     * @param bool $analyzer
     * @param int $phraseSlop
     * @return mixed
     */
    public function getFieldQuery($fieldName, $value, $analyzer = true, $phraseSlop = 10)
    {
        $queryString = new \Elastica\Query\QueryString($value);
        $queryString->setDefaultField($fieldName);
        $queryString->setAnalyzeWildcard($analyzer);
        $queryString->setPhraseSlop((int)$phraseSlop);

        return $queryString;
    }

    /**
     * Типа regexp запроса.
     *
     * @param string $fieldName
     * @param string $value
     * @return mixed
     */
    public function getWildCardQuery($fieldName, $value)
    {
        $queryString = new \Elastica\Query\Wildcard($fieldName, $value);

        return $queryString;
    }
}