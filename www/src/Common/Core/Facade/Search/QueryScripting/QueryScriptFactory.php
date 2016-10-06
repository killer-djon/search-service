<?php
namespace Common\Core\Facade\Search\QueryScripting;
/**
 * Класс задающий скриптовые поля
 * т.е. поля содержащие динамические данные при извлечении
 */
class QueryScriptFactory implements QueryScriptFactoryInterface
{
    /**
     * Получаем объект скрипта
     *
     * @param string|\Elastica\Script $script
     * @param array $params Параметры передаваемые в скрипт
     * @return \Elastica\Script
     */
    public function getScript($script)
    {
        $_script = new \Elastica\Script($script);
        $_script->setFieldsSource();
        return $_script;
    }
}