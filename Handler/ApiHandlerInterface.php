<?php
namespace Lynx\ApiBundle\Handler;
interface ApiHandlerInterface
{
    public function get($id);
    public function getAll();
    public function post(array $parameters);
 }
