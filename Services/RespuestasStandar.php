<?php
use Symfony\Component\HttpFoundation\JsonResponse;
namespace Lynx\ApiBundle\Services;
class RespuestasStandar {
    private $om;
    private $entityClass;
    private $repository;
    public function __construct($entityClass) {
        $this->entityClass = $entityClass;
    }
     public function CamposRetornados($user)
    {
        $datos = [];
        $datos[] = $user->getName();
        $datos[] = $user->getUsername();
        $datos[] = $user->getEmail();
        return $datos;
    }
    public function Contenido($contenido, $totalRegistros=0, $numeroPaginas=0)
    {
        return array('data' => $contenido, 
                'X-Total-Count' => $totalRegistros,
                'X-Numero-Paginas' => $numeroPaginas,
            );
    }
}
