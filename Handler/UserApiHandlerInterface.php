<?php
namespace Lynx\ApiBundle\Handler;
interface UserApiHandlerInterface
{
    public function getUser($id);
    public function getAllUser();
    public function post(array $parameters);
 }
