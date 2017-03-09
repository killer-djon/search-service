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
}