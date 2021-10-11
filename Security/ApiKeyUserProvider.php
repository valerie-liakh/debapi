<?php
namespace Lynx\ApiBundle\Security;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\ORM\EntityManager;
class ApiKeyUserProvider implements UserProviderInterface
{
    protected $em;
    public function __construct(EntityManager $entityManager){
            $this->em = $entityManager;
}
    public function getUsernameForApiKey($apiKey)
    {
        $username = "admin";
        return $username;
    }
    public function loadUserByUsername($username)
    {
        $entity = $this->em->getRepository('LynxBundle:User')->find(4);
        return $entity;
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
