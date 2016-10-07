<?php
/**
 * Main controller trait
 * to manipulate with requests params
 */
namespace Common\Core\Controller;

use Common\Core\Constants\RequestConstant;
use Common\Core\Facade\Service\Geo\GeoPointService;
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
}