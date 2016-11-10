<?php
namespace Common\Core\Facade\Search\QueryScripting;

interface QueryScriptFactoryInterface
{
    /**
     * Формируем объект скрипта
     *
     * @param string $script
     * @param array|null $params Параметры передаваемые в скрипт
     * @param string $lang Язык исполнения скрипта (default: js)
     * @return \Elastica\Script
     */
    public function getScript($script, array $params = null, $lang = \Elastica\Script::LANG_JS);

    /**
     * Формируем поле скрипта с расчетом дистанции
     * необходимо передать параметры для скрипта
     *
     * @param string $pointField Название поля с локацией (должно содержать lat, lon)
     * @param array|\Common\Core\Facade\Service\Geo\GeoPointServiceInterface $geopoint Объект геоданных
     * @param string $lang Язык скрипта (default: groovy)
     * @throws ElasticsearchException
     * @return null|\\Elastica\Script
     */
    public function getDistanceScript($pointField, $geopoint, $lang = \Elastica\Script::LANG_JS);

    /**
     * Формируем поле скрипта с пересекающихся тегов (интересов)
     * найденых пользователей с заданным
     *
     * @param string $tagsField Название поля где хранятся теги
     * @param array $tags набор тегов для рассчета
     * @param string $lang Язык скрипта (default: groovy)
     * @throws ElasticsearchException
     * @return \Elastica\Script
     */
    public function getTagsIntersectScript($tagsField, array $tags, $lang = \Elastica\Script::LANG_JS);

    /**
     * Формируем поле скрипта с пересекающихся тегов (интересов)
     * в процентном отношении
     *
     * @param string $tagsField Название поля где хранятся теги
     * @param array $tags набор тегов для рассчета
     * @param string $lang Язык скрипта (default: groovy)
     * @throws ElasticsearchException
     * @return \Elastica\Script
     */
    public function getTagsIntersectInPercentScript($tagsField, array $tags, $lang = \Elastica\Script::LANG_JS);

}