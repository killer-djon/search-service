<?php

namespace Common\Security;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Translation\TranslatorInterface;

class ApiTokenUserProvider implements UserProviderInterface
{
    const TOKEN_FIELD = '_id';
    const USER_ID_FIELD = '_userId';
    const ACCOUNT_USER_ID_FIELD = '_id';
    const ROLES = '_roles';
    const ENABLED = '_isEnabled';

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @var \MongoClient
     */
    private $mainStorage;

    /**
     * @var \MongoCollection
     */
    private $userCollection;

    /**
     * @var \MongoClient
     */
    private $tokenStorage;

    /**
     * @var \MongoCollection
     */
    private $tokenCollection;

    /**
     * @param string $mainDsn
     * @param string $mainDb
     * @param string $userCollection
     * @param string $tokenDsn
     * @param string $tokenDb
     * @param string $tokenCollection
     */
    public function __construct($mainDsn, $mainDb, $userCollection, $tokenDsn, $tokenDb, $tokenCollection)
    {
        $this->mainStorage = new \MongoClient($mainDsn);
        $this->userCollection = $this->mainStorage->selectDB($mainDb)->selectCollection($userCollection);

        $this->tokenStorage = new \MongoClient($tokenDsn);
        $this->tokenCollection = $this->tokenStorage->selectDB($tokenDb)->selectCollection($tokenCollection);
    }

    /**
     * Получаем ссылку на транслятор
     *
     * @param TranslatorInterface $translator
     * @return void
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Находим пользователя в базе по его tokenId
     * если такого пользовтаеля нет в базе
     * тогда генерим исключение
     *
     * @param string $username TokenID пользователя полученный из заголовка
     * @throws BadCredentialsException
     * @return User
     */
    public function loadUserByUsername($username)
    {
        $res = $this->tokenCollection->findOne([self::TOKEN_FIELD => new \MongoId($username)]);

        if (!is_null($res) && !empty($res)) {
            $user = $res;
            $roles = [];
            $enabled = false;

            if (isset($user[self::USER_ID_FIELD])) {
                $userAccount = $this->userCollection
                    ->findOne(
                        [self::ACCOUNT_USER_ID_FIELD => $user[self::USER_ID_FIELD]],
                        [self::ROLES => true, self::ENABLED => true]
                    );

                if (!is_null($userAccount) && !empty($userAccount)) {
                    $account = $userAccount;
                    $roles = isset($account[self::ROLES]) ? $account[self::ROLES] : [];
                    $enabled = isset($account[self::ENABLED]) ? $account[self::ENABLED] : false;
                }
            }

            return new User(
                $user[self::USER_ID_FIELD],
                null,
                $roles,
                $enabled
            );
        } else {
            throw new BadCredentialsException();
        }
    }

    public function refreshUser(UserInterface $user)
    {
        throw new UnsupportedException();
    }

    public function supportsClass($class)
    {
        throw new UnsupportedException();
    }
}