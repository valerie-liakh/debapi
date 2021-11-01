<?php
use Symfony\Component\HttpFoundation\JsonResponse;
namespace Lynx\ApiBundle\Services;
class RespuestasCabecera {
    public function EjecucionNoPermitida($errores)
    {
        return array('error' => $errores,'cod' => 405);
    }
    public function Contenido($contenido, $totalRegistros=0, $numeroPaginas=0, $paginaActual=1)
    {
        return array('data' => $contenido, 
                'totalCount' => $totalRegistros,
                'numeroPaginas' => $numeroPaginas,
                'paginaActual' => intval($paginaActual)
            );
    }
}
