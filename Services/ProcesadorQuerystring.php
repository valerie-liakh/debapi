<?php
namespace Lynx\ApiBundle\Services;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Collection;
class ProcesadorQuerystring {
    function procesarSeleccion($campos) {
        foreach (explode(',', $campos) as $campo)
            if (in_array($campo, $this->camposSeleccionables))
                $this->seleccion[] = $campo;
            else
                $this->errores[] = "El campo $campo no es seleccionable o no existe";
    }
    function __construct(RequestStack $request, $validator) {
        $this->request = $request->getCurrentRequest();
        $this->validator = $validator;
    }
}
