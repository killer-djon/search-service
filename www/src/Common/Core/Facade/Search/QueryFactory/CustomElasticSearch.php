<?php
/**
 * Вспомогательный класс
 * унаследованный от базового поискового еластики
 * необходим для внедрения функционала манипуляции с индексами
 *
 * @author Leshanu E
 */

namespace Common\Core\Facade\Search\QueryFactory;

use Elastica\Search;
use Common\Util\ArrayHelper;

class CustomElasticSearch extends Search
{
    /**
     * Очищаем все индексы ранее установленные в кеше
     * например для использования только 1 индекса в дальнейшем
     *
     * @return Search
     */
    public function clearIndices()
    {
        $this->_indices = [];
    }

    /**
     * Удаляем определенный индекс из списка
     * уже закешированных данных
     *
     * @return Search
     */
    public function removeIndex($indexName)
    {
        if ($this->hasIndex($indexName)) {
            ArrayHelper::remove($this->_indices, $indexName);
        }
    }

}