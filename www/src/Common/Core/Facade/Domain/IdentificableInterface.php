<?php
namespace Common\Core\Facade\Domain;

/**
 * Интерфейс идентифицируемого класса
 */
interface IdentificableInterface
{
    /**
     * @return null|mixed
     */
    public function getId();
}
