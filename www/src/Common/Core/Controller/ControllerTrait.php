<?php
/**
 * Main controller trait
 * to manipulate with requests params
 */
namespace Common\Core\Controller;

use Common\Core\Constants\RequestConstant;
use Common\Core\Facade\Service\Geo\GeoPointService;
use RP\SearchBundle\Services\Transformers\AbstractTransformer;
use Symfony\Component\HttpFoundation\Request;

trait ControllerTrait
{
    /**
     * Параметр запроса offset
     *
     * @var int $_skip
     */
    private $_skip;

    /**
     * Параметр кол-ва count
     *
     * @var int $_count
     */
    private $_count;

    /**
     * ОБъект представляющий GeoPoint service
     *
     * @var \Common\Core\Facade\Service\Geo\GeoPointService $_geoPoint
     */
    private $_geoPoint;

    /**
     * Моделируем данные для псевдо-пагинации
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return void
     */
    public function parseSkipCountRequest(Request $request)
    {
        $this->_skip = $request->get(RequestConstant::SEARCH_SKIP_PARAM, RequestConstant::DEFAULT_SEARCH_SKIP);
        $this->_count = $request->get(RequestConstant::SEARCH_LIMIT_PARAM, RequestConstant::DEFAULT_SEARCH_LIMIT);
    }

    /**
     * Моделируем данные для геопозиционирования
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return void
     */
    public function parseGeoPointRequest(Request $request)
    {
        $this->_geoPoint = new GeoPointService(
            $request->get((!is_null(RequestConstant::SHORT_LATITUDE_PARAM) ? RequestConstant::SHORT_LATITUDE_PARAM : RequestConstant::LONG_LATITUDE_PARAM)),
            $request->get(!is_null(RequestConstant::SHORT_LONGITUTE_PARAM) ? RequestConstant::SHORT_LONGITUTE_PARAM : RequestConstant::LONG_LONGITUTE_PARAM),
            $request->get(RequestConstant::RADIUS_PARAM)
        );
    }

    public function getSkip()
    {
        return $this->_skip;
    }

    public function getCount()
    {
        return $this->_count;
    }

    /**
     * Получаем объект Гео сервиса
     *
     * @return \Common\Core\Facade\Service\Geo\GeoPointServiceInterface
     */
    public function getGeoPoint()
    {
        return $this->_geoPoint;
    }

    /**
     * Временный метод который добавляет ключ tagNames
     * во все массивы где есть tags
     * - необходимо для поддержки старых версий приложения
     * Метод будет пробегатся рекурсивно по массиву вниз
     *
     * @param array $inputArray Ссылка на искходный массив
     * @return void
     */
    public function restructTagsField(&$inputArray)
    {
        array_walk($inputArray, function (&$arItem) {
            if (is_array($arItem) && !isset($arItem['tags'])) {
                $this->restructTagsField($arItem);
            }

            if (isset($arItem['tags'])) {
                $arItem['tagNames'] =& $arItem['tags'];
            }
        });
    }

    /**
     * Временный метод который меняет
     * название полей lat, lon
     * на длиные названия longitude, latitude
     *
     * @param array $inputArray Ссылка на искходный массив
     * @return void
     */
    public function restructLocationField(&$inputArray)
    {
        array_walk($inputArray, function (& $item) {
            if (is_array($item) && !isset($item['lat']) && !isset($item['lon'])) {
                $this->restructLocationField($item);
            }

            isset($item['lat']) && $item['latitude'] = $item['lat'];
            isset($item['lon']) && $item['longitude'] = $item['lon'];
        });

        foreach ($inputArray as $key => & $arItem) {
            isset($arItem['location']['point']) && $arItem['latitude'] =& $arItem['location']['point']['lat'];
            isset($arItem['location']['point']) && $arItem['longitude'] =& $arItem['location']['point']['lon'];
        }
    }

    /**
     * Набор полей для преобразования
     * поддержка старых приложений
     *
     * @var array
     */
    protected $fieldsMap = [
        'fullname'          => 'fullName',
        'tagsInPercent'     => 'matchingInterestsInPercents',
        'tagsCount'         => 'tagsPct',
        'distanceInPercent' => 'distancePct',
    ];

    /**
     * Набор полей которые не нужно исключать из набора данных
     * по общим правилам мы исключаем поля пустые или массивы пустые
     * а это исключать не надо иначе выводятся NULL значения
     *
     * @var array
     */
    private $neededKeys = [
        'surname'           => ' ',
        'text'              => ' ',
        'allCheckinsCount'  => ' ',
        'distance'          => 0,
        'distanceInPercent' => 0,
        //'isFriendshipRequestReceived' => 'false',
        //'isFriend' => 'false',
        //'isFollower' => 'false',
        //'isFriendshipRequestSent' => 'false',
    ];

    /**
     * Временный метод
     * предназначен для замены имени ключа в объектах ответа
     *
     * @param array $inputArray
     * @return array
     */
    public function changeKeysName($inputArray)
    {
        $return = [];
        foreach ($inputArray as $key => $value) {
            if (array_key_exists($key, $this->fieldsMap)) {
                $key = $this->fieldsMap[$key];
            }

            if (is_array($value)) {
                $value = $this->changeKeysName($value);
            }

            if (array_key_exists($key, $this->neededKeys) && empty($value)) {
                $value = $this->neededKeys[$key];
            }

            $return[$key] = $value;
        }

        return $return;
    }

    /**
     * Набор полей динамических
     * которые надо преобразовать в скалярные типы для клиента
     * это некий костыль для совместимости
     *
     * @param array
     */
    private $tagsMatchFields = [
        'tagsInPercent'               => 'intval',
        'matchingInterestsInPercents' => 'intval',
        'tagsCount'                   => 'intval',
        'tagsPct'                     => 'intval',
        'distanceInPercent'           => 'intval',
        'distancePct'                 => 'intval',
        'distance'                    => 'floatval',
        'allCheckinsCount'  => 'intval',
        // ugcStat
        'placesCount' => 'intval',
        "checkinPlacesCount" => 'intval',
        "eventsCount" => 'intval',
        "willComeEventsCount" => 'intval',
        "interestsCount" => 'intval',
        "helpOffersCount" => 'intval',
        "friendsCount" => 'intval',
        
		//'isFriendshipRequestReceived' => 'boolval',
        //'isFriend' => 'boolval',
        //'isFollower' => 'boolval',
        //'isFriendshipRequestSent' => 'boolval',
    ];

    /**
     * Метод выполняющий преобразование строкового значения поля
     * в скалярный вид (для совместимости)
     *
     * @param array $inputArray Массив исходный
     * @return array Результат обработки массива
     */
    public function revertToScalarTagsMatchFields(& $inputArray)
    {
        if( empty($inputArray) )
        {
            return [];
        }
        foreach ($inputArray as $key => & $item) {
            if (is_array($item) && !empty($item)) {
                $item = $this->revertToScalarTagsMatchFields($item);
            }

            if (array_key_exists($key, $this->tagsMatchFields)) {
                if (!isset($inputArray['distance'])
                    && isset($inputArray['distanceInPercent']) && $inputArray['distanceInPercent'] == 0
                    || isset($inputArray['distancePct']) && $inputArray['distancePct'] == 0
                ) {

                    if (isset($inputArray['distancePct'])) {
                        unset($inputArray['distancePct']);
                    }
                    if (isset($inputArray['distanceInPercent'])) {
                        unset($inputArray['distanceInPercent']);
                    }
                }

                $item = call_user_func($this->tagsMatchFields[$key], $item);
            }
        }

        return $inputArray;
    }

    /**
     * Временный метод
     * убриаем из объектов пустые значения
     * необходимо для совместимости старых приложений
     *
     * @param array $inputArray
     * @return array
     */
    public function excludeEmptyValue($inputArray)
    {
        $filtered = AbstractTransformer::array_filter_recursive($inputArray);

        return $this->revertToScalarTagsMatchFields($filtered);
    }
}