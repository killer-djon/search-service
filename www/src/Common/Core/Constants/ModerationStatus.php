<?php
/**
 * Статусы модерации
 */
namespace Common\Core\Constants;

abstract class ModerationStatus
{
	/**
     * На модерации (черновик)
     *
     * @var int DIRTY
     */
    const DIRTY = 0;
    
    /**
     * Прошел модерацию
     *
     * @var int OK
     */
    const OK = 1;
    
    /**
     * Отклонен модератором
     *
     * @var int REJECTED
     */
    const REJECTED = 2;
    
    /**
     * Непонятный статус модерации
     *
     * @var int NOT_IN_PROMO
     */
    const NOT_IN_PROMO = 3;
    
    /**
     * Удален модератором
     *
     * @var int DELETED
     */
    const DELETED = 4;
    
    /**
     * Восстановлен модератором
     *
     * @var int RESTORED
     */
    const RESTORED = 5;
}