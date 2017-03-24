<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 22.02.17
 * Time: 19:32
 */

namespace Common\Core\Constants;

class RelationType
{
    /** Дружба */
    const FRIENDSHIP = 'friendship';

    /** Запрос в друзья */
    const FRIENDSHIP_REQUEST = 'friendshipRequest';

    /** детализация запроса в друзья */
    const FRIENDSHIP_REQUEST_INITIATOR = 'friendshipRequestReceived';

    /** Подписка */
    const FOLLOWING = 'following';

    /** Никаких */
    const NONE = 'none';
}