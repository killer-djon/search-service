<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 19.05.17
 * Time: 19:27
 */

namespace Common\Core\Constants;

abstract class NewsFeedSections
{
    /** Новости */
    const FEED_ALL = "all";
    const FEED_NEWS = "news";

    /** Рекомендации */
    const FEED_RECOMMENDATIONS = "recommendations";

    /** Фото */
    const FEED_PHOTOS = "photos";

    /**
     * @deprecated
     */
    const FEED_PHOTO = "photo";

    /** Медиа */
    const FEED_MEDIA = "media";

    /** Ответы */
    const FEED_ANSWERS = "answers";

    const FEED_WALL = "wall";

    const FEED_NOTIFICATIONS = "notifications";
}