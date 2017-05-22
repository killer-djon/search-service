<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 22.05.17
 * Time: 14:32
 */

namespace RP\SearchBundle\Services\Mapping;

abstract class PostSearchMapping extends AbstractSearchMapping
{
    /** Контекст поиска */
    const CONTEXT = 'posts';

    /** Индекс еластика по умолчанию */
    const DEFAULT_INDEX = 'newsfeed';

    /** @const Поле ID стены */
    const POST_WALL_ID = 'wallId';

    /** @const Был ли опубликован пост */
    const POST_IS_POSTED = 'isPosted';

    /** @const Поле удалености поста */
    const POST_IS_REMOVED = 'isRemoved';
}