<?php
namespace RP\SearchBundle\Services\Traits;

use Common\Core\Constants\NewsFeedSections;
use Common\Core\Constants\RelationType;
use Common\Core\Constants\UserEventType;

trait PeopleServiceTrait
{
    /**
     * Набор связей по умолчанию
     *
     * @var array
     */
    private $_relations = [
        'isFriend'                    => false,
        'isFollower'                  => false,
        'isFriendshipRequestSent'     => false,
        'isFriendshipRequestReceived' => false,
    ];

    /**
     * Значения отношений для провреки
     *
     * @var array
     */
    private $_relationsType = [
        RelationType::FRIENDSHIP                   => 'isFriend',
        RelationType::FOLLOWING                    => 'isFollower',
        RelationType::FRIENDSHIP_REQUEST           => 'isFriendshipRequestSent',
        RelationType::FRIENDSHIP_REQUEST_INITIATOR => 'isFriendshipRequestReceived',
    ];

    /**
     * Типы пользовательских событий в зависимости от выбранного раздела
     *
     * @see NewsFeedSections
     * @param string $section
     * @return array
     */
    public function getUserEventsGroups($section)
    {
        switch ($section) {
            case NewsFeedSections::FEED_MEDIA:
                $eventGroup = [
                    UserEventType::POST,
                ];
                break;
            case NewsFeedSections::FEED_PHOTO:
                $eventGroup = [
                    UserEventType::CHANGE_AVATAR,
                    UserEventType::POST,
                ];
                break;
            case NewsFeedSections::FEED_ANSWERS:
                $eventGroup = [
                    UserEventType::COMMENT,
                    UserEventType::LIKE,
                ];
                break;
            case NewsFeedSections::FEED_RECOMMENDATIONS:
                $eventGroup = [];
                break;

            case NewsFeedSections::FEED_NOTIFICATIONS:
                return [
                    // события, receiver'ом является текущий пользователь, автор события не важен
                    'personal' => [
                        // Приходит от RP, только в уведомления
                        //UserEventType::PEOPLE_AROUND,
                        // Только уведомления
                        UserEventType::INVITE_EVENT_EVENT,
                        // Не понимаю, как эти записи продолжают появлятся в базе? По коду API не нашел
                        UserEventType::INVITE_EVENT_EVENT_LEGACY,
                        // Только уведомления
                        UserEventType::COMMENT,
                        // Только уведомления
                        UserEventType::LIKE,
                        // Только уведомления
                        UserEventType::MENTIONED,
                        // Только уведомления
                        UserEventType::TODAY_EVENT,
                        // Только уведомления,
                        UserEventType::MODERATION,
                    ],

                    // автором события являются друзья пользователя
                    'friends'  => [
                        // Только уведомления
                        UserEventType::BIRTHDAY,
                    ],

                    // события, receiver'ом является текущий пользователь, автор события не является ни самим пользователем, ни его другом
                    'others'   => [
                        // Только уведомления
                        UserEventType::INVITE_EVENT_EVENT,
                        // От друзей - в ленту, в уведомления - от модератора если наша сущность
                        UserEventType::NEW_EVENT_UPDATED,
                        // От друзей - в ленту, в уведомления - от модератора если наша сущность
                        UserEventType::NEW_PLACE_UPDATED,
                        // Удаление сущности - непонятно почему только тут
                        UserEventType::DELETE_EVENT,
                        // Только уведомления
                        UserEventType::LIKE,
                        // Только уведомления
                        UserEventType::COMMENT,
                        // Только уведомления
                        UserEventType::MENTIONED,
                        // От друзей - в ленту, в уведомления - к нашему событию от незнакомцев
                        UserEventType::WILL_COME,
                        // От друзей - в ленту, в уведомления - в наше место от незнакомцев
                        UserEventType::CHECKIN,
                        // Сейчас не используется
                        UserEventType::RECOMMENDATION,
                    ],

                    // События, автором которых является сам пользователь
                    'author'   => [
                        UserEventType::NEW_FRIEND,
                    ],
                ];
                break;

            case NewsFeedSections::FEED_NEWS:
                return [
                    /**
                     * События, receiver'ом и автором которых является текущий пользователь,
                     * Для событий генерящихся множество раз (например, eventUpdated рассылается всем, кто willCome)
                     * Иначе одна правка события отобразится миллион раз в ленте, если туда идут миллион человек
                     */
                    'personal' => [
                        // От друзей - в ленту, в уведомления - от модератора если наша сущность
                        UserEventType::NEW_EVENT_UPDATED,
                    ],

                    // Только от друзей и самого пользователя
                    'friends'  => [
                        // Только в ленту
                        UserEventType::NEW_PHOTO_ALBUM,
                        // Только в ленту
                        UserEventType::NEW_PHOTOS,
                        // Только в ленту
                        UserEventType::NEW_PLACE,
                        // Только в ленту
                        UserEventType::NEW_EVENT,
                        // От друзей - в ленту, в уведомления - от модератора если наша сущность
                        UserEventType::NEW_PLACE_UPDATED,
                        // Только в ленту
                        UserEventType::CHANGE_AVATAR,
                        // Только в ленту
                        UserEventType::HELP_ADDED,
                        // От друзей - в ленту, в уведомления - к нашему событию от незнакомцев
                        UserEventType::WILL_COME,
                        // От друзей - в ленту, в уведомления - в наше место от незнакомцев
                        UserEventType::CHECKIN,
                        // Кто-то (только друзья) лайкнули пост  другого человека, которого нет в друзьях
                        //UserEventType::LIKE,
                        // Кто-то (только друзья) прокомментировали пост другого человека, которого нет в друзьях
                        //UserEventType::COMMENT
                    ],
                    'others'   => [
                        UserEventType::CHECKIN,
                    ],
                ];
                break;
        }

        $eventGroup[] = UserEventType::DELETE_EVENT;

        return $eventGroup;
    }

    /**
     * Устанавливаем в предустановленный набор
     * связь по ключу
     *
     * @param string $relationKey
     * @param bool $relationType
     */
    public function setKeyRelation($relationKey, $relationType = false)
    {
        if (array_key_exists(strtolower($relationKey), array_change_key_case($this->_relationsType, CASE_LOWER))) {

            $this->_relations[$this->_relationsType[$relationKey]] = (bool)$relationType;
        }
    }

    /**
     * Возвращает набор отношений/связей с пользователем
     *
     * @return array
     */
    public function getRelations()
    {
        return $this->_relations;
    }

    /**
     * Возвращет отношение с пользователем
     * по ключу
     *
     * @param string $relationKey
     * @return bool
     */
    public function getKeyRelation($relationKey)
    {
        if (array_key_exists($relationKey, $this->_relations)) {
            return $this->_relations[$relationKey];
        }
    }

    /**
     * Устанавливаем отношение с пользователем
     * в том случае если мы смотрим другой профиль
     *
     * @param array $relations Набор ключей отношений
     * @param string $userId Текущий пользователь
     * @return array
     */
    public function setRelations(array $relations, $userId)
    {

        if (isset($relations[$userId])) {
            $this->setKeyRelation($relations[$userId], true);
        }

        return $this->getRelations();

    }
}