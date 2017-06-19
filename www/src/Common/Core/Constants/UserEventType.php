<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 19.05.17
 * Time: 19:28
 */

namespace Common\Core\Constants;

abstract class UserEventType
{
    /** Пост */
    const POST = 'post';

    /** Новый фотоальбом */
    const NEW_PHOTO_ALBUM = 'newPhotoAlbum';

    /** Добавление фото в альбом */
    const NEW_PHOTOS = 'newPhotos';

    /** Рекомендация */
    const RECOMMENDATION = 'recommendation';

    /** Добавление в друзья */
    const NEW_FRIEND = 'newFriend';

    /** Изменение контактных данных */
    const UPDATE_CONTACT_INFO = 'updateContactInfo';

    /** Смена аватара */
    const CHANGE_AVATAR = 'changeAvatar';

    /** Изменение местоположения */
    const CHANGE_LOCATION = 'changeLocation';

    /** Комментарий */
    const COMMENT = 'comment';

    /** Мне нравится */
    const LIKE = 'like';

    /** Я пойду */
    const WILL_COME = 'willCome';

    /** День рождения */
    const BIRTHDAY = 'birthday';

    /** Создание нового места */
    const NEW_PLACE = 'newPlace';

    /** Создание нового события */
    const NEW_EVENT = 'newEvent';

    /** Обновление нового места */
    const NEW_PLACE_UPDATED = 'updatedPlace';

    /** Обновление нового события */
    const NEW_EVENT_UPDATED = 'updatedEvent';

    /** Обновление нового места */
    const CHECKIN = 'checkin';

    /** Удаление события */
    const DELETE_EVENT = 'delete';

    /** Инвайт */
    const INVITE_EVENT_EVENT = 'inviteEvent';

    /** Устаревшее название события приглашения на мероприятие */
    const INVITE_EVENT_EVENT_LEGACY = 'invite';

    /** Мероприятие сегодня */
    const TODAY_EVENT = 'todayEvent';

    /** Упомянут в сущности */
    const MENTIONED = 'mentioned';

    /** добавил "могу помочь" */
    const HELP_ADDED = 'helpAdded';

    /** удалил "могу помочь" */
    const HELP_REMOVED = 'helpRemoved';

    /** Новые люди рядом */
    const PEOPLE_AROUND = 'peopleAround';

    /** Модерация */
    const MODERATION = 'moderation';
}