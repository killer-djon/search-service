<?php
namespace Common\Core\Facade\Reporting;

use Doctrine\Common\Collections\Criteria;

/**
 * Интерфейс репозитория хранилища для чтения на основе Doctrine
 */
interface DoctrineRepositoryInterface
{
    /**
     * @return \Doctrine\ODM\MongoDB\DocumentManager
     */
    public function getDocumentManager();

    /**
     * @param string $documentName Класс документа
     * @return \Doctrine\ODM\MongoDB\Query\Builder
     */
    public function createQueryBuilder($documentName);

    /**
     * @param string $documentName Класс документа
     * @param \Doctrine\Common\Collections\Criteria $criteria
     * @return \Doctrine\ODM\MongoDB\Collection
     */
    public function matching($documentName, Criteria $criteria);

}
