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

    /**
     * Формируем поле скрипта с расчетом дистанции
     * необходимо передать параметры для скрипта
     *
     * @param string $pointField Название поля с локацией (должно содержать lat, lon)
     * @param array|\Common\Core\Facade\Service\Geo\GeoPointServiceInterface $geopoint Объект геоданных
     * @param string $lang Язык скрипта (default: groovy)
     * return null|\\Elastica\Script
     */
    public function getDistanceScript($pointField, $geopoint, $lang = \Elastica\Script::LANG_JS);

    /**
     * Формируем поле скрипта
     * рассчитав по дистанции расстояние в процентном отношении
     * относительно переданной точки
     *
     * @param string $pointField Название поля с локацией (должно содержать lat, lon)
     * @param array|\Common\Core\Facade\Service\Geo\GeoPointServiceInterface $geopoint Объект геоданных
     * @param string $lang Язык скрипта (default: groovy)
     * @throws ElasticsearchException
     * @return null|\Elastica\Script
     */
    public function getDistanceInPercentScript($pointField, $geopoint, $lang = \Elastica\Script::LANG_JS);

    /**
     * Формируем поле скрипта с пересекающихся тегов (интересов)
     * найденых пользователей с заданным
     *
     * @param string $tagsField Название поля где хранятся теги
     * @param array $tags набор тегов для рассчета
     * @param string $lang Язык скрипта (default: groovy)
     * @return null|\\Elastica\Script
     */
    public function getTagsIntersectScript($tagsField, array $tags, $lang = \Elastica\Script::LANG_JS);
}