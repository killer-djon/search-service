<?php
namespace Common\Core\Domain;

use Common\Core\Facade\Domain\IdentificableInterface;

/**
 * Базовый класс сущности домена 
 */
abstract class DomainEntity implements IdentificableInterface
{
	/**
     * Уникальный идентификатор сущности
     *
     * @var string
     */
    protected $_id;

    /**
     * Дата создания.
     *
     * @var \DateTime
     */
    protected $_createdAt;

    /**
     * @param $id
     * @throws \InvalidArgumentException
     */
    public function __construct($id)
    {
        if (empty($id)) {
            throw new \InvalidArgumentException();
        }

        $this->_id = $id;
        $this->_createdAt = new \DateTime();
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Дата создания сущности
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->_createdAt;
    }
    
    /**
     * @return string
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * Дата создания сущности
     *
     * @return \DateTime
     */
    public function setCreatedAt(\DateTime $date)
    {
        $this->_createdAt = $date;
    }

    /**
     * Сбрасывает значение createdAt на now()
     * Необходимо для ситуаций "сущность добавлена заново", так как у нас ничего не удаляется
     */
    public function resetCreatedAt()
    {
        $this->_createdAt = new \DateTime();
    }

    /**
     * Метод перекрываемый трейтом TagEntity
     *
     * @return boolean
     */
    public function isTagged()
    {
        return false;
    }

}
