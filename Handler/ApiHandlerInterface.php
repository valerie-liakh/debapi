<?php
namespace Lynx\ApiBundle\Handler;
interface ApiHandlerInterface
{
    public function get($id);
    public function getAll($camposOrdenables);
    public function post(array $parameters);
 }
