<?php
use Symfony\Component\HttpFoundation\JsonResponse;
namespace Lynx\ApiBundle\Services;
class RespuestasCabecera {
    public function EjecucionNoPermitida($errores)
    {
        return array('error' => $errores,'cod' => 405);
    }
    public function Contenido($contenido, $totalRegistros=0, $numeroPaginas=0)
    {
        return array('data' => $contenido, 
                'X-Total-Count' => $totalRegistros,
                'X-Numero-Paginas' => $numeroPaginas,
            );
    }
}
