<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 12.10.16
 * Time: 14:39
 */

namespace Common\Core\Facade\Service;

interface ReportingServiceInterface
{
    /**
     * Получаем ID записи
     *
     * @return string
     */
    public function getId();

    /**
     * Получаем дату создания записи
     *
     * @return \DateTime
     */
    public function getCreatedAt();
}