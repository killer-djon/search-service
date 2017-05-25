<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 24.05.17
 * Time: 18:55
 */

namespace RP\SearchBundle\Services\Mapping;

abstract class UserEventSearchMapping extends AbstractSearchMapping
{

    /** Индекс еластика по умолчанию */
    const DEFAULT_INDEX = 'newsfeed';

    /** Контекст поиска */
    const CONTEXT = 'userevents';

    /** Имя поля, содержащего идентификатор аватара */
    const AVATAR_ID_FIELD = 'avatar';

    /** Имя поля, содержащего тип события */
    const TYPE_FIELD = 'type';

    /** Имя поля, указывающее содержит ли пост медиа */
    const MEDIA_POST_FIELD = 'isMediaPost';

    /** Имя поля, указывающее содержит ли пост фото */
    const PHOTO_POST_FIELD = 'isPhotoPost';

    /** Имя поля, содержащего идентификатор поста (только для событий связанных с постами) */
    const POST_ID_FIELD = 'post';

    /** Имя поля, содержащего идентификатор коментария */
    const COMMENT_ID_FIELD = 'comment';

    /** Имя поля, содержащего идентификатор лайка */
    const LIKE_ID_FIELD = 'like';

    /** Имя поля, содержащего идентификатор альбома */
    const PHOTO_ALBUM_ID_FIELD = 'photoAlbum';

    /** Имя поля, содержащего идентификаторы фотографий */
    const PHOTO_ID_FIELD = 'photos';

    /** Имя поля, содержащего дату создания документа */
    const CREATED_AT_FIELD = 'createdAt';

    /** Имя поля, содержащего идентификатор автора сущности, к которой относится лайк */
    const ENTITY_AUTHOR_ID_FIELD = 'entityAuthor';

    /** Имя поля, содержащего идентификатор места */
    const PLACE_ID_FIELD = 'place';

    /** Имя поля, содержащего идентификатор события места */
    const EVENT_ID_FIELD = 'event';

    /** Признак удаленного документа */
    const IS_REMOVED_FIELD = 'isRemoved';

    /** Добавленные друзья */
    const FRIENDS_IDS_FIELD = 'friends';

    const RECEIVER_USER_FIELD = 'receiver';

    const RECEIVER_USER_ID_FIELD = 'receiver.id';
}