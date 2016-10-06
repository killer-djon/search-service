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

    public function parseSkipCountRequest(Request $request)
    {
        $this->_skip = $request->get(RequestConstant::SEARCH_SKIP_PARAM, RequestConstant::DEFAULT_SEARCH_SKIP);
        $this->_count = $request->get(RequestConstant::SEARCH_LIMIT_PARAM, RequestConstant::DEFAULT_SEARCH_LIMIT);

        $this->_geoPoint = new GeoPointService(
            $request->get(RequestConstant::LATITUDE_PARAM),
            $request->get(RequestConstant::LONGITUTE_PARAM)
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