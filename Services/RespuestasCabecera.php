<?php
namespace Lynx\ApiBundle\Services;
use Symfony\Component\HttpFoundation\JsonResponse;
class RespuestasCabecera {
    public function RecursoNoEncontrado($info='Parametros no encontrados',$message='No se enviaron los parámetros para esta operación', $code=404 )
    {
        return new JsonResponse([$info, $message], $code);
    }
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
