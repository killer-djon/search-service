<?php

namespace Common\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;

class ApiTokenAuthenticator implements SimplePreAuthenticatorInterface
{
    /**
     * Считываем токен из хидера
     *
     * @param Request $request
     * @param string $providerKey
     * @return PreAuthenticatedToken
     */
    public function createToken(Request $request, $providerKey)
    {
        $apiToken = $request->headers->get('tokenId');

        if (is_null($apiToken)) {
            throw new BadRequestHttpException("Header's TokenId param does not exists, check and try again");
        }

        if (!$apiToken) {
            throw new BadCredentialsException();
        }

        return new PreAuthenticatedToken('anonymous', $apiToken, $providerKey);
    }

    /**
     * Проверям валидность токена
     *
     * @param TokenInterface $token
     * @param string $providerKey
     * @return bool
     */
    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return
            $token instanceof PreAuthenticatedToken
            && \MongoId::isValid($token->getCredentials())
            && $token->getProviderKey() === $providerKey;
    }

    /**
     * Аутентифицируемся через провайдер
     *
     * @param TokenInterface $token
     * @param UserProviderInterface $userProvider
     * @param $providerKey
     * @return PreAuthenticatedToken
     */
    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {

        $user = $userProvider->loadUserByUsername($token->getCredentials());


        return new PreAuthenticatedToken(
            $user,
            $token->getCredentials(),
            $providerKey,
            $user->getRoles()
        );
    }
}