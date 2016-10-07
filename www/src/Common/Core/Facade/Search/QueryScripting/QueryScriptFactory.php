<?php
namespace Common\Core\Facade\Search\QueryScripting;

use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use Elastica\Exception\ElasticsearchException;

/**
 * Класс задающий скриптовые поля
 * т.е. поля содержащие динамические данные при извлечении
 */
class QueryScriptFactory implements QueryScriptFactoryInterface
{
    /**
     * Формируем объект скрипта
     *
     * @param string|\Elastica\Script $script
     * @param array $params Параметры передаваемые в скрипт
     * @return \Elastica\Script
     */
    public function getScript($script)
    {
        $_script = new \Elastica\Script($script);

        return $_script;
    }

    /**
     * Метод просто преобразовывает в параметра запроса дистанции данные поступившие
     * @param mixed $geopoint
     * @return array
     */
    private function getGeopointParams($geopoint)
    {
        if($geopoint instanceof GeoPointServiceInterface)
        {
            return [
                'lon'        => $geopoint->getLongitude(),
                'lat'        => $geopoint->getLatitude(),
                'radius'     => $geopoint->getRadius()
            ];
        }elseif(is_array($geopoint))
        {
            return $geopoint;
        }

        return null;

    }

    /**
     * Формируем поле скрипта с расчетом дистанции
     * необходимо передать параметры для скрипта
     *
     * @param string $pointField Название поля с локацией (должно содержать lat, lon)
     * @param array|\Common\Core\Facade\Service\Geo\GeoPointServiceInterface $geopoint Объект геоданных
     * @param string $lang Язык скрипта (default: groovy)
     * @throws ElasticsearchException
     * @return null|\Elastica\Script
     */
    public function getDistanceScript($pointField, $geopoint, $lang = \Elastica\Script::LANG_JS)
    {
        $params = $this->getGeopointParams($geopoint);
        try
        {
            $params = array_merge($params, [
                'pointField' => $pointField
            ]);

            $script = "
            if (!doc[pointField].empty) {
                distance = doc[pointField].distanceInKm(lat, lon);
                distance.toFixed(3)
            }else
            {
                distance = 0
            }
            ";

            return new \Elastica\Script(
                $script,
                $params,
                $lang
            );
        }catch(ElasticsearchException $e)
        {
            throw new ElasticsearchException($e);
        }
    }

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
    public function getDistanceInPercentScript($pointField, $geopoint, $lang = \Elastica\Script::LANG_JS)
    {
        $params = $this->getGeopointParams($geopoint);

        try{
            $params = array_merge($params, [
                'pointField' => $pointField,
            ]);

            $script = "
            if (!doc[pointField].empty) {
                var distance = distance = doc[pointField].distanceInKm(lat, lon);
                distanceInPercent = distance * 100 / (radius / 1000);
                Math.round(distanceInPercent) + '%'
            }else
            {
                distanceInPercent = 100 + '%'
            }
            ";

            return new \Elastica\Script(
                $script,
                $params,
                $lang
            );
        }catch(ElasticsearchException $e)
        {
            throw new ElasticsearchException($e);
        }
    }
}