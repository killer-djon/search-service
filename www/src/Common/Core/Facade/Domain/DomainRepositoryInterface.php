<?php

namespace Common\Core\Facade\Domain;

use Common\Core\Domain\DomainEntity;

/**
 * Интерфейс репозитория домена
 */
interface DomainRepositoryInterface
{
    /**
     * Извлекает сущность заданного типа по идентификатору
     *
     * @param string $entityId
     * @param string $entityType
     * @return null|object
     */
    public function getEntity($entityId, $entityType);

    /**
     * Извлекает список сущностей заданного типа по идентификаторам
     *
     * @param array $entityIds
     * @param string $entityType
     * @return null|object[]
     */
    public function getEntityList(array $entityIds, $entityType);

    /**
     * Сохраняет новую сущность
     *
     * @param \Common\Core\Domain\DomainEntity $entity
     */
    public function save(DomainEntity $entity);

    /**
     * Удаляет сущность
     * @param \Common\Core\Domain\DomainEntity $entity
     */
    public function delete(DomainEntity $entity);
}
