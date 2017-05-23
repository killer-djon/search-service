<?php
/**
 * Сервис поиска в коллекции постов
 * т.е. поиск по ленте
 */

namespace RP\SearchBundle\Services;

use Common\Core\Constants\RequestConstant;
use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use Elastica\Query\FunctionScore;
use Elastica\Query\MultiMatch;
use RP\SearchBundle\Services\Mapping\AbstractSearchMapping;
use RP\SearchBundle\Services\Mapping\PostSearchMapping;

/**
 * Class NewsFeedSearchService
 *
 * @package RP\SearchBundle\Services
 */
class NewsFeedSearchService extends AbstractSearchService
{
    /**
     * ПОиск всех постов пользователя по ID ленты
     *
     * @param string $userId ID пользователя
     * @param string $wallId ID ленты
     * @param int $skip
     * @param int $count
     * @return array
     */
    public function searchPostsByWallId($userId, $wallId, $skip = 0, $count = RequestConstant::DEFAULT_SEARCH_LIMIT)
    {
        $this->setConditionQueryMust([
            $this->_queryConditionFactory->getTermQuery(PostSearchMapping::POST_WALL_ID, $wallId),
        ]);

        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermFilter([PostSearchMapping::POST_IS_POSTED => true]),
        ]);

        $this->setSortingQuery($this->_sortingFactory->getFieldSort(AbstractSearchMapping::CREATED_AT_FIELD, 'desc'));

        $queryMatch = $this->createQuery($skip, $count);

        return $this->searchDocuments($queryMatch, PostSearchMapping::CONTEXT);
    }

    /**
     * Получаем объекта поста по его ID
     *
     * @param string $userId ID пользователя
     * @param string $postId ID поста
     * @return array
     */
    public function searchPostById($userId, $postId)
    {
        $this->setFilterQuery([
            $this->_queryFilterFactory->getTermFilter([PostSearchMapping::POST_IS_POSTED => true]),
        ]);

        return $this->searchRecordById(PostSearchMapping::CONTEXT, AbstractSearchMapping::IDENTIFIER_FIELD, $postId);
    }
}