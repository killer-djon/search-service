<?php
namespace Common\Core\Facade\Search\QueryScripting;

interface QueryScriptFactoryInterface
{
    /**
     * Получаем объект скрипта
     *
     * @param string|\Elastica\Script $script
     * @param array $params Параметры передаваемые в скрипт
     * @return \Elastica\Script
     */
    public function getScript($script);
}