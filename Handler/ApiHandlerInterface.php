<?php
namespace Lynx\ApiBundle\Handler;
interface ApiHandlerInterface
{
    public function get($id);
    public function getAll($camposOrdenables, $camposSeleccionables, $camposConsultables,$camposFiltrables);
    public function post(array $parameters);
 }
