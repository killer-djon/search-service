<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 18.07.17
 * Time: 14:47
 */

namespace Common\Core\Constants;


abstract class SettingsNotifications implements \ArrayAccess
{
    const NONE = 'none';
    const SHOW = 'show';
    const PUSH = 'push';
    const DEFAULT_VALUE = self::SHOW . ',' . self::PUSH;

    public static $accessTypes = [
        self::NONE,
        self::SHOW,
        self::PUSH,
    ];

    // Используются ли настройки
    private static $__all = self::DEFAULT_VALUE;
    // Дефолтные значения для свежих настроек
    private static $_birthday = self::DEFAULT_VALUE;
    private static $_newFriend = self::DEFAULT_VALUE;
    private static $_inviteEvent = self::DEFAULT_VALUE;
    private static $_todayEvent = self::DEFAULT_VALUE;
    private static $_moderation = self::DEFAULT_VALUE;
    private static $_newEvent = self::DEFAULT_VALUE;
    private static $_updatedEvent = self::DEFAULT_VALUE;
    private static $_updatedPlace = self::DEFAULT_VALUE;
    private static $_like = self::DEFAULT_VALUE;
    private static $_comment = self::DEFAULT_VALUE;
    private static $_willCome = self::DEFAULT_VALUE;
    private static $_recommendation = self::DEFAULT_VALUE;
    private static $_delete = self::DEFAULT_VALUE;
    private static $_checkin = self::DEFAULT_VALUE;
    private static $_mentioned = self::DEFAULT_VALUE;
    private static $_peopleAround = self::DEFAULT_VALUE;
    private static $_chat = self::DEFAULT_VALUE;
    private static $_friendshipRequest = self::DEFAULT_VALUE;


    /**
     * получаем флаг использования настроек вообще в приложении
     *
     * @return string
     */
    public static function getAll()
    {
        return self::$__all;
    }

    public static function checkSettingsValue($settingName)
    {
        return property_exists(self::class, $settingName) ?? false;
    }
}