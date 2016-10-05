<?php
/**
 * Строит запрос с расчетом очков релевантности для возможных друзей
 */
namespace RP\SearchBundle\Services;

class CustomScoreBuilderScript implements ScoreBuilderInterface
{
    /** Имя параметра для передачи интересов пользователя */
    const TAGS_PARAMETER = 'tags';

    /** Названия параметра радиуса поиска в километрах */
    const RADIUS_PARAMETER = 'radius';

    /** Название параметра текущего местоположения пользователя, формат [ lat, lng ]  */
    const POINT_PARAMETER = 'point';

    const M_RADIUS = 2;

    /**
     * Возвращает текст скрипта расчета очков релевантности результатов поиска
     *
     * @return string
     */
    public function getScript()
    {
        $tagsValue   = self::TAGS_PARAMETER;
        $radiusValue = self::RADIUS_PARAMETER;
        $pointValue  = self::POINT_PARAMETER;
        $mRadius     = self::M_RADIUS;

        $script = <<<EOS
distanceInPercent = 0.0;
distance = 0.0;

if ($pointValue != null && doc.containsKey("location.point")) {

    if (!doc["location.point"].empty) {
        distance = doc["location.point"].distanceInKm(${pointValue}[0], ${pointValue}[1]);
    } else {
        distance = null;
    }

    if (distance == null || distance >= 2 * $radiusValue) {
        distanceInPercent = 0;
    } else if (distance <= $radiusValue) {
        distanceInPercent = 100;
    } else {
        distanceInPercent = 100 * (1 - (2 * $radiusValue - distance) / $radiusValue);
    };

} else {
    distanceInPercent = 0;
};

count = 0.0;
tagsCount = 0.0;
tagsInPercent = 0.0;

if ({$tagsValue}.size() > 0 && doc["tags.id"].values.size() > 0) {
    foreach (tagId: $tagsValue) {
        tagsCount++;
        foreach (docTagId: doc["tags.id"].values) {
            if (docTagId == tagId) {
                count++;
            }
        }
    };
    tagsInPercent = count / tagsCount * 100;
};

sortCategory = 0;

if (distanceInPercent >= 50 && tagsInPercent >= 50) {
    sortCategory = 1;
} else if (distanceInPercent >= 30 || tagsInPercent >= 30) {
    sortCategory = 2;
} else if (distanceInPercent > 0 || tagsInPercent > 0) {
    sortCategory = 3;
} else {
    sortCategory = 4;

};

customScore = 0.0;
customScore = (5 - sortCategory) + (1000.0 / (1000 * distance > 1000 ? Math.round(distance * 1000) : 1000) + tagsInPercent / 100.0) / 2.001;

return customScore;
EOS;
        return str_replace("\n", "", $script);
        //return preg_replace('/([\\n ]+)/m', '', $script);
    }
}