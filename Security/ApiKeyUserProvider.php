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
         $user = $this->em->getRepository('LynxBundle:User')->findByToken($apiKey);
         $username = $user[0]->getUsername();
        return $username;
    }
    public function loadUserByUsername($username)
    {
        $user = $this->em->getRepository('LynxBundle:User')->findByUsername($username);
        return $user;
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
