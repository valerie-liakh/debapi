<?php
namespace Lynx\ApiBundle\Security;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
class ApiKeyUserProvider implements UserProviderInterface
{
    public function getUsernameForApiKey($apiKey)
    {
        $username = "admin";
        return $username;
    }
    public function loadUserByUsername($username)
    {
        return new User(
            $username,
            null,
            array('ROLE_API')
        );
    }
    public function refreshUser(UserInterface $user)
    {
        throw new UnsupportedUserException();
    }
    public function supportsClass($class)
    {
        return User::class === $class;
    }
}
