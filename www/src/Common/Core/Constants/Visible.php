<?php
/**
 * Значения видимости места
 */
namespace Common\Core\Constants;

abstract class Visible
{
    /**
     * Значение "Никому"
     */
    const NONE = 'none';

    /**
     * Значение "Только друзьям"
     */
    const FRIEND = 'friend';

    /**
     * Значение "Для всех, кроме друзей"
     */
    const NOT_FRIEND = 'not_friend';

    /**
     * Значение "Для всех"
     */
    const ALL = 'all';

    /**
     * Проверяет значение видимости на соответствие допустимым значениям
     * @static
     * @param $visible
     * @return bool
     */
    public static function isValid($visible)
    {
        $variants = self::getVisibleVariants();

        return in_array($visible, $variants);
    }

    public static function getVisibleVariants()
    {
        return array(self::NONE, self::FRIEND, self::NOT_FRIEND, self::ALL);
    }
}