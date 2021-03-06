<?php
namespace Lynx\ApiBundle\Services;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Collection;
class ProcesadorQuerystring {
    private $parametros = [];
    private $request;
    private $validator;
    private $generales = [];
    private $excepciones = ['sort', 'q', 'fields', 'page', 'per_page'];
    private $registroPorPaginaPermitidos = [1, 2, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50];
    private $usarGenerales = true;
    function __construct(RequestStack $request, $validator) {
        $this->request = $request->getCurrentRequest();
        $this->validator = $validator;
    }
    private $camposOrdenables = [];
    public function setCamposOrdenables($camposOrdenables) {
        $this->camposOrdenables = $camposOrdenables;
    }
    public function ejecutar() {
        $filtros = $this->camposFiltrables;
        $this->camposFiltrables = $this->getParametros();
        $parametros = array_merge($this->request->query->all(), $this->parametros);
        foreach ($parametros as $parametro => $valor) {
            $this->procesarParametro($parametro, $valor);
        }
        $this->camposFiltrables = $filtros;
        return (count($this->errores) == 0);
    }
    function procesarParametro($parametro, $valor) {
        if (in_array($parametro, $this->excepciones)) {
            switch ($parametro) {
                case 'fields':
                    $this->procesarSeleccion($valor);
                    break;
                case 'sort':
                    $this->procesarOrden($valor);
                    break;
                case 'q':
                    $this->procesarBusqueda($valor);
                    break;
                case 'page':
                    $this->procesarNumeroPagina($valor);
                    break;
                case 'per_page':
                    $this->procesarRegistrosPorPagina($valor);
                    break;
            }
        }
        elseif (array_key_exists($parametro, $this->camposFiltrables)) {
           $this->procesarFiltro($parametro, $valor);
        } else {
            echo "Error";
            $this->errores[] = "El parametro $parametro no puede ser procesado o no existe";
            exit();
        }
    }
    function procesarOrden($campos) {
        foreach (explode(',', $campos) as $campo) {
            $campo = explode(':', $campo);
            if (in_array($campo[0], $this->camposOrdenables)) {
                $this->orden[$campo[0]] = 'ASC';
                if (count($campo) > 1)
                    $this->orden[$campo[0]] = $campo[1];
            } else {
                $this->errores[] = 'El valor ' . $campo[0] . ' no puede ser utilizado para ordenar la consulta';
            }
        }
    }
    private $pagina = 1;
    public function getPagina() {
        return $this->pagina;
    }
    private $registrosPorPagina = 10;
    public function getRegistrosPorPagina() {
        return $this->registrosPorPagina;
    }
    function procesarNumeroPagina($pagina) {
        if (is_numeric($pagina))
            $this->pagina = $pagina;
        else
            $this->errores[] = "El parametro page debe ser un entero";
    }
    function procesarRegistrosPorPagina($numeroRegistros) {
        if (is_numeric($numeroRegistros) & in_array($numeroRegistros, $this->registroPorPaginaPermitidos))
            $this->registrosPorPagina = $numeroRegistros;
        else
            $this->errores[] = 'El parametro per_page debe ser un entero y corresponder con alguno de los siguientes valores [' . implode(', ', $this->registroPorPaginaPermitidos) . ']';
    }
    private $errores = [];
    public function getErrores() {
        return $this->errores;
    }
    public function getOrden() {
        return $this->orden;
    }
    function procesarSeleccion($campos) {
        foreach (explode(',', $campos) as $campo) {
            if (in_array($campo, $this->camposSeleccionables)) {
                $this->seleccion[] = $campo;
            } else {
                $this->errores[] = "El campo $campo no es seleccionable o no existe";
            }
        }
    }
    public function getParametros() {
        $parametros = [];
        if ($this->usarGenerales) {
            $parametros = array_merge($this->generales, $this->camposFiltrables);
        } else {
            $parametros = $this->camposFiltrables;
        }
        return $parametros;
    }
    public function setUsarGenerales($usarGenerales) {
        $this->usarGenerales = $usarGenerales;
    }
    private $camposSeleccionables = [];
    public function setCamposSeleccionables($camposSeleccionables) {
        $this->camposSeleccionables = $camposSeleccionables;
    }
    private $camposFiltrables = [];
    public function setCamposFiltrables($camposFiltrables) {
        $this->camposFiltrables = $camposFiltrables;
    }
    private $stringAgrupables = '';
    public function setStringAgrupables($stringAgrupables) {
        $this->stringAgrupables = $stringAgrupables;
    }
    private $camposConsultables = [];
    public function setCamposConsultables($camposConsultables) {
        $this->camposConsultables = $camposConsultables;
    }
    private $filtros = [];
    public function getFiltros() {
        return $this->filtros;
    }
    private $busqueda = null;
    public function getBusqueda() {
        return $this->busqueda;
    }
    private $orden = [];
    private $seleccion = [];
    public function getStringAgrupables() {
        return $this->stringAgrupables;
    }
    public function getSeleccion() {
        return $this->seleccion;
    }
    function procesarBusqueda($valor) {
        if (count($this->camposConsultables) > 0)
            $this->busqueda = [
                'campos' => $this->camposConsultables,
                'valor' => $valor
            ];
    }
    function procesarFiltro($campo, $valor) {
        switch ($this->camposFiltrables[$campo]['style']) {
            case 'flat':
                if ($this->validarCampo($campo, $valor)) {
                    $this->filtros[$campo] = $valor;
                    break;
                }
            case 'range':
                $valores = explode(',', $valor);
                if (count($valores) > 2)
                    $this->errores[] = "El campo $campo contiene mas valores de los permitidos";
                if (count($valores) == 2) {
                    $errores = 0;
                    $errores = ($this->validarCampo('ent.'.$campo . '_min', $valores[0], $campo)) ? $errores : $errores++;
                    $errores = ($this->validarCampo('ent.'.$campo . '_max', $valores[1], $campo)) ? $errores : $errores++;
                    if ($errores == 0)
                        if ($valores[1] < $valores[0])
                            $this->errores[] = "El rango del campo $campo es inv??lido";
                        else
                            $this->filtros[$campo] = $valores;
                } else
                if ($this->validarCampo($campo, $valor))
                    $this->filtros[$campo] = $valor;
                break;
            case 'list':
                $valores = explode(',', $valor);
                if (count($valores) > 1) {
                    $errores = 0;
                    foreach ($valores as $index => $valor) {
                        $errores = ($this->validarCampo($campo . '_' . $index, $valor, $campo)) ? $errores : $errores++;
                    }
                    if ($errores == 0) {
                        $this->filtros[$campo] = $valores;
                    }
                } else {
                    if ($this->validarCampo($campo, $valor)){
                        $this->filtros[$campo] = $valor;                      
                    }
                    break;
                }
        }
    }
    function validarCampo($campo, $valor, $clave = '') {
        if ($clave == '') {
            $clave = $campo;
        }
        $validacion = new Collection([
            $campo => $this->camposFiltrables[$clave]['validaciones']
        ]);
        $data = [$campo => $valor];
        $error = $this->validator->validate($data, $validacion);
        if (count($error) > 0)
            $this->errores[] = $error[0]->getPropertyPath() . ':' . $error[0]->getMessage();
        return (count($error) == 0);
    }
    public function setPagina_NRegistros() {
        $this->pagina = 1;
        $this->registrosPorPagina = 1;
    }
}
