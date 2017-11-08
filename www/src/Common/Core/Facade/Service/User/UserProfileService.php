<?php
/**
 * Сервис предоставляющий геттеры/сеттеры для профиля пользователя
 *
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
     * @var string Имя пользователя
     */
    protected $_name;

    /**
     * @var string Фамилия пользователя
     */
    protected $_surname;

    /**
     * @var string Полное имя фамилия пользователя
     */
    protected $_fullname;

    /**
     * @var array список ID друзей пользователя
     */
    protected $_friendList;

    /**
     * @var string ID стены
     */
    protected $_wallId;

    /**
     * @var array Настройки пользовательские
     */
    private $_userSettings;

    /**
     * Дата регистрации пользователя
     *
     * @var \DateTime
     */
    private $_registrationDate;

    /**
     * Список блокированных пользователей
     *
     * @var array
     */
    private $_blockedUsers;

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
            (isset($data['location']['point']['lat']) ? $data['location']['point']['lat'] : null),
            (isset($data['location']['point']['lon']) ? $data['location']['point']['lon'] : null)
        );
        $this->_tags = isset($data['tags']) && !empty($data['tags']) ? $data['tags'] : [];
        $this->_name = $data['name'];
        $this->_surname = $data['surname'];
        $this->_fullname = $data['fullname'];
        $this->_friendList = !empty($data['friendList']) ? $data['friendList'] : [];
        $this->_wallId = $data['wallId'];
        $this->_userSettings = isset($data['settings']) ? $data['settings'] : [];
        $this->_registrationDate = isset($data['registrationDate']) ? new \DateTime($data['registrationDate']) : null;
        $this->_blockedUsers = !empty($data['blockedUsers']) ? $data['blockedUsers'] : [];
    }

    /**
     * Получаем список блокированных пользователей
     *
     * @return array
     */
    public function getBlockedUsers()
    {
        return $this->_blockedUsers;
    }

    /**
     * Получаем дату регистрации пользоваля
     * @return \DateTime
     */
    public function getRegistrationDate()
    {
        return $this->_registrationDate;
    }

    /**
     * Получаем пользовательские настройки профиля
     *
     * @param string $key Получить конкретный раздел настроек
     * @return array
     */
    public function getUserSettings($key = null)
    {
        return !is_null($key) && !empty($this->_userSettings[$key]) ? $this->_userSettings[$key] : $this->_userSettings;
    }

    /**
     * Получаем ID стены пользователя
     *
     * @return string
     */
    public function getWallId()
    {
        return $this->_wallId;
    }

    /**
     * Получаем массив ID друзей пользователя
     *
     * @return array
     */
    public function getFriendList()
    {
        return $this->_friendList;
    }

    /**
     * Получаем статус пользователя в текущий момент
     *
     * @return boolean
     */
    public function getIsOnline()
    {
        return $this->_isOnline;
    }

    /**
     * Получаем время последнего посещения пользователя
     *
     * @return \DateTime
     */
    public function getLastActivity()
    {
        return $this->_lastActivity;
    }

    /**
     * Получаем пол пользователя
     *
     * @return string (M|F)
     */
    public function getGender()
    {
        return $this->_gender;
    }

    /**
     * Получаем плокацию пользователя
     *
     * @return GeoPointServiceInterface
     */
    public function getLocation()
    {
        return $this->_location;
    }

    /**
     * Получаем интересы пользователя
     *
     * @return array
     */
    public function getTags()
    {
        return $this->_tags;
    }

    /**
     * Получаем имя пользователя
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Получаем фамилию пользователя
     *
     * @return string
     */
    public function getSurname()
    {
        return $this->_surname;
    }

    /**
     * Получаем полное имя/фамилию пользователя
     *
     * @return string
     */
    public function getFullname()
    {
        return $this->_fullname;
    }

    /**
     * Преобразуем в читабельный массив для вывода на экран
     *
     * @return array Массив данных
     */
    public function toArray()
    {
        return [
            'id'           => $this->getId(),
            'name'         => $this->getName(),
            'surname'      => $this->getSurname(),
            'fullname'     => $this->getFullname(),
            'gender'       => $this->getGender(),
            'isOnline'     => $this->getIsOnline(),
            'lastActivity' => $this->getLastActivity()->format('c'),
            'location'     => [
                'point' => [
                    'longitude' => $this->getLocation()->getLongitude(),
                    'latitude'  => $this->getLocation()->getLatitude(),
                ],
            ],
            'tags'         => $this->getTags(),
        ];
    }
}