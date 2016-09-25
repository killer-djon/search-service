<?php

namespace Common\Core\Facade\Domain;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Common\Collections\Criteria;

use Common\Core\Exceptions\Domain\DomainRepositoryException;
use Common\Core\Domain\DomainEntity;
use Common\Core\Facade\Reporting\DoctrineRepositoryInterface;

/**
 * Репозиторий домена с использованием Doctrine
 */
class DoctrineDomainRepository implements DomainRepositoryInterface, DoctrineRepositoryInterface
{
    /** Имя поля с идентификатором сущности */
    const ID_FIELD = '_id';

    /**
     * Ссылка на менеджер объектов Doctrine
     * @var \Doctrine\ODM\MongoDB\DocumentManager
     */
    protected $_documentManager;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * Алиас сущностей в Doctrine
     * @var string
     */
    private $_entityAlias;

    /**
     * @param \Symfony\Component\HttpKernel\Log\LoggerInterface;
     * @param \Doctrine\ODM\MongoDB\DocumentManager $documentManager
     * @param string $entityAlias
     */
    public function __construct(LoggerInterface $logger, DocumentManager $documentManager, $entityAlias)
    {
        $this->_logger = $logger;
        $this->_documentManager = $documentManager;
        $this->_entityAlias = $entityAlias;
    }

    /**
     * Собрать путь до вложенного поля.
     *
     * @param array $fields
     * @return string Field for expression.
     */
    protected static function assemblePath(array $fields)
    {
        return implode('.', $fields);
    }

    /**
     * Извлекает сущность заданного типа по идентификатору
     *
     * @param string $entityId
     * @param string $entityType
     * @return null|\Common\Core\Domain\DomainEntity
     * @throws DomainRepositoryException
     */
    public function getEntity($entityId, $entityType)
    {
        // Идентификатор не должен быть пустым
        if (empty($entityId)) {
            return null;
        }

        try {
            $entity = $this->_documentManager->find($this->_getDocumentName($entityType), $entityId);
        } catch(DomainRepositoryException $ex) {
            throw new DomainRepositoryException(DomainRepositoryException::READ_ERROR, $ex->getCode(), $ex);
        }

        return $entity;
    }

    /**
     * Извлекает список сущностей заданного типа по идентификаторам
     *
     * @param array $entityIds
     * @param string $entityType
     * @throws DomainRepositoryException
     * @return null|object[]
     */
    public function getEntityList(array $entityIds, $entityType)
    {
        if (empty($entityIds) || empty($entityType)) {
            return array();
        }

        // mongo не найдет записей, если тип ключа будет не строка
        $entityIds = array_map(function ($id) { return "$id"; }, $entityIds);

        $queryBuilder = $this->_createQueryBuilder($entityType);
        $queryBuilder->field(self::ID_FIELD)->in(array_values($entityIds));

        $query = $queryBuilder->getQuery();
        $entityList = $this->_executeQuery($query);

        return $entityList->toArray();
    }

    /**
     * Извлекает список сущностей заданного типа по значению поля
     *
     * @param string $entityType
     * @param string $field
     * @param string $value
     * @return null|object[]
     */
    public function getEntityListFieldEqual($entityType, $field, $value)
    {
        $queryBuilder = $this->_createQueryBuilder($entityType);
        $query = $queryBuilder->field($field)->equals($value)->getQuery();

        return $this->_getSingleResult($query);
    }

    /**
     * Извлекает список сущностей заданного типа по заданному условию
     *
     * @param string $entityType Класс документа
     * @param \Doctrine\Common\Collections\Criteria $criteria
     * @return \Doctrine\ODM\MongoDB\Collection
     */
    public function matching($entityType, Criteria $criteria)
    {
        return $this->getDocumentManager()->getRepository($entityType)->matching($criteria);
    }

    /**
     * Сохраняет новую сущность
     *
     * @param \Common\Core\Domain\DomainEntity $entity
     * @throws DomainRepositoryException
     */
    public function save(DomainEntity $entity)
    {
        try {
            $this->_documentManager->persist($entity);
        }
        catch(DomainRepositoryException $ex) {
            throw new DomainRepositoryException(DomainRepositoryException::SAVE_ERROR, $ex->getCode(), $ex);
        }
    }

    /**
     * Удаляет сущность
     *
     * @param \Common\Core\Domain\DomainEntity $entity
     * @throws DomainRepositoryException
     */
    public function delete(DomainEntity $entity)
    {
        try {
            $this->_documentManager->remove($entity);
        }
        catch(DomainRepositoryException $ex) {
            throw new DomainRepositoryException(DomainRepositoryException::DELETE_ERROR, $ex->getCode(), $ex);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentManager()
    {
        return $this->_documentManager;
    }

    /**
     * {@inheritDoc}
     */
    public function createQueryBuilder($documentName)
    {
        return $this->getDocumentManager()->createQueryBuilder($documentName);
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Генерирует имя документа по типу сущности
     *
     * @param string $entityType
     * @return string
     */
    protected function _getDocumentName($entityType)
    {
        return sprintf('%s:%s', $this->_entityAlias, $entityType);
    }

    /**
     * Возвращает билдер запросов по имени сущности
     *
     * @param string $entityName
     * @return \Doctrine\ODM\MongoDB\Query\Builder
     */
    protected function _createQueryBuilder($entityName)
    {
        return $this->_documentManager->createQueryBuilder(
            $this->_getDocumentName($entityName)
        );
    }

    /**
     * Выполняет запрос и возвращает его результат
     *
     * @param Query $query
     * @return mixed
     * @throws \Common\Core\Facade\Reporting\DoctrineRepositoryInterface
     */
    protected function _executeQuery(Query $query)
    {
        try {
            $queryResult = $query->execute();
        } catch (DoctrineRepositoryInterface $ex) {
            throw new DomainRepositoryException(DomainRepositoryException::READ_ERROR, $ex->getCode(), $ex);
        }

        return $queryResult;
    }

    /**
     * Возвращает единственный результат запроса
     *
     * @param Query $query
     * @return object
     * @throws \Common\Core\Facade\Reporting\DoctrineRepositoryInterface
     */
    protected function _getSingleResult(Query $query)
    {
        try {
            $singleResult = $query->getSingleResult();
        } catch (DomainRepositoryException $ex) {
            throw new DomainRepositoryException(DomainRepositoryException::READ_ERROR, $ex->getCode(), $ex);
        }

        return $singleResult;
    }
}
