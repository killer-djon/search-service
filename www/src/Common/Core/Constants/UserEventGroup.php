<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 24.05.17
 * Time: 19:00
 */

namespace Common\Core\Constants;

abstract class UserEventGroup
{
    // события, receiver'ом является текущий пользователь, автор события не важен
    const PERSONAL = 'personal';

    // автором события являются друзья пользователя
    const FRIENDS = 'friends';

    // события, receiver'ом является текущий пользователь, автор события не является другом пользователя
    const OTHERS = 'others';

    const SPECIAL = 'special';

    // события, автором которого является сам пользователь
    const AUTHOR = 'author';
}