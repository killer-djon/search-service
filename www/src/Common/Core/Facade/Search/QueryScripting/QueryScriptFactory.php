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
     * @param array|null $params Параметры передаваемые в скрипт
     * @param string $lang Язык исполнения скрипта (default: js)
     * @return \Elastica\Script
     */
    public function getScript($script, array $params = null, $lang = \Elastica\Script::LANG_JS)
    {
        $_script = new \Elastica\Script($script, $params, $lang);

        return $_script;
    }

    /**
     * Метод просто преобразовывает в параметра запроса дистанции данные поступившие
     *
     * @param mixed $geopoint
     * @return array
     */
    private function getGeopointParams($geopoint)
    {
        if ($geopoint instanceof GeoPointServiceInterface) {
            return [
                'lon'    => $geopoint->getLongitude(),
                'lat'    => $geopoint->getLatitude(),
                'radius' => $geopoint->getRadius(),
            ];
        } elseif (is_array($geopoint)) {
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
        try {
            $params = array_merge($params, [
                'pointField' => $pointField,
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
        } catch (ElasticsearchException $e) {
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

        try {
            $params = array_merge($params, [
                'pointField' => $pointField,
            ]);

            $script = "
            if (!doc[pointField].empty && radius) {
                var distance = doc[pointField].distanceInKm(lat, lon);
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
        } catch (ElasticsearchException $e) {
            throw new ElasticsearchException($e);
        }
    }

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
    public function getTagsIntersectScript($tagsField, array $tags, $lang = \Elastica\Script::LANG_JS)
    {
        if(!empty($tags))
        {
            try
            {
                $tags = array_map(function($tag){
                    return $tag['id'];
                }, $tags);

                $script = "
                tagsCount = 0;
                count = 0;
                totalCount = 0;
                list = [];
                
                if(tagsValue.size() > 0 && doc[tagIdField].values.size() > 0){
                     for(var i = 0, len = tagsValue.size(); i < len; i++){
                        ++tagsCount;
                        for(var j = 0, lenJ = doc[tagIdField].values.size(); j < lenJ; j++){
                            if( tagsValue[i] == doc[tagIdField][j] ){
                                ++count;
                                list.push(doc[tagIdField][j]);
                            }
                        }
                    }
                }
                totalCount = count.toString();
            ";

                return new \Elastica\Script($script, [
                    'tagIdField' => $tagsField,
                    'tagsValue' => $tags
                ], $lang);
            }catch(ElasticsearchException $e){
                throw new ElasticsearchException($e);
            }
        }

        return new \Elastica\Script("");
    }

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
    public function getTagsIntersectInPercentScript($tagsField, array $tags, $lang = \Elastica\Script::LANG_JS)
    {
        if(!empty($tags))
        {
            try{
                $tags = array_map(function($tag){
                    return $tag['id'];
                }, $tags);

                $script = "
                count = 0;
                tagsCount = 0;
                tagInPercent = 0;
                
                if(tagsValue.size() > 0 && doc[tagIdField].values.size() > 0){
                    for(var i = 0, len = tagsValue.size(); i < len; i++){
                        ++tagsCount;
                        for(var j = 0, lenJ = doc[tagIdField].values.size(); j < lenJ; j++){
                            if( tagsValue[i] == doc[tagIdField][j] ){
                                ++count;
                            }
                        }
                    }
                    
                    tagInPercent = count/tagsCount*100;
                    tagInPercent = Math.round(tagInPercent) + '%'
                }else
                {
                    tagInPercent += '%'
                }
            ";

                return new \Elastica\Script($script, [
                    'tagIdField' => $tagsField,
                    'tagsValue' => $tags
                ], $lang);
            }catch(ElasticsearchException $e){
                throw new ElasticsearchException($e);
            }
        }

        return new \Elastica\Script("");
    }
}