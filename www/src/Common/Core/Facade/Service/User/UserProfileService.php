<?php
/**
 * Сервис предоставляющий геттеры/сеттеры для профиля пользователя
 * @todo геттеры/сеттеры можно добавлять по мере поступления задачи
 * на текущий момент это все что необходимо для работы
 */
namespace Common\Core\Facade\Service\User;

use Common\Core\Facade\Service\Geo\GeoPointService;
use Common\Core\Facade\Service\Geo\GeoPointServiceInterface;
use Common\Core\Facade\Service\ReportingService;

class UserProfileService extends ReportingService
{
    /**
     * @var boolean
     */
    protected $_isOnline;

    /**
     * @var \DateTime
     */
    protected $_lastActivity;

    /**
     * @var string (M|F)
     */
    protected $_gender;

    /**
     * @var GeoPointServiceInterface
     */
    protected $_location;

    /**
     * @var array Набор тегов пользователя
     */
    protected $_tags;


    /**
     * @inheritdoc
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->_isOnline = (bool)$data['onlineStatus']['isOnline'];
        $this->_lastActivity = new \DateTime($data['onlineStatus']['lastActivity']);

        $this->_gender = strtoupper($data['gender']);
        $this->_location = new GeoPointService(
            $data['location']['point']['lat'],
            $data['location']['point']['lon']
        );
        $this->_tags = isset($data['tags']) && !empty($data['tags']) ? $data['tags'] : [];
    }

    /**
     * Получаем статус пользователя в текущий момент
     * @return boolean
     */
    public function getIsOnline()
    {
        return $this->_isOnline;
    }

    /**
     * Получаем время последнего посещения пользователя
     * @return \DateTime
     */
    public function getLastActivity()
    {
        return $this->_lastActivity;
    }

    /**
     * Получаем пол пользователя
     * @return string (M|F)
     */
    public function getGender()
    {
        return $this->_gender;
    }

    /**
     * Получаем плокацию пользователя
     * @return GeoPointServiceInterface
     */
    public function getLocation()
    {
        return $this->_location;
    }

    /**
     * Получаем интересы пользователя
     * @return array
     */
    public function getTags()
    {
        return $this->_tags;
    }
}