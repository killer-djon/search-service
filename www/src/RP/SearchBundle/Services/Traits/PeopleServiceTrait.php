<?php
namespace RP\SearchBundle\Services\Traits;

use Common\Core\Constants\RelationType;

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