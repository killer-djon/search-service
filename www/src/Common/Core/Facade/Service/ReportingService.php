<?php
/**
 * Основной класс каркаса сервисов по работе с сущностями
 */
namespace Common\Core\Facade\Service;

abstract class ReportingService implements ReportingServiceInterface
{
    /**
     * Уникальный идентификатор записи
     *
     * @var string $_id
     */
    protected $_id;

    /**
     * Дата создания записи
     *
     * @var \DateTime $_createdAt
     */
    protected $_createdAt;

    /**
     * Для всех сущностей устанавливаем дату и ID
     * @param array $data Массив данных сущности
     */
    public function __construct(array $data)
    {
        $this->_id = $data['id'];
        $this->_createdAt = (isset($data['createdAt']) ? new \DateTime($data['createdAt']) : null);
    }

    /**
     * Устанавливаем id записи
     *
     * @param string $id
     * @return self
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * Устанавливаем дату создания записи
     *
     * @param mixed $createdAt
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->_createdAt = new \DateTime($createdAt);
    }

    /**
     * Получаем ID записи
     *
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Получаем дату создания записи
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->_createdAt;
    }
}