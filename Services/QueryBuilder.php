<?php
namespace Lynx\ApiBundle\Services;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Lynx\ApiBundle\Components\ApiResult;
use StringTemplate;
use Symfony\Component\Validator\Constraints\Collection;
class QueryBuilder {
    private $connection;
    private $template = "SELECT :distinct :campos FROM :tabla :condicionales :agrupacion :orden :limites";
    private $condicionales = [];
    private $validator;
    private $numeroPaginas = 0;
    private $nomEntidad = 'ent';
    private $campos = [];
    private $manager;
    function __construct(ProcesadorQuerystring $procesador, Connection $connection, $validator, ObjectManager $om) {
        $this->procesador = $procesador;
        $this->connection = $connection;
        $this->validator = $validator;
        $this->manager = $om;
    }
    private $errores = [];
    public function getErrores() {
        return $this->errores;
    }
    public function getProcesador() {
        return $this->procesador;
    }
    private $entidad;
    public function setEntidad($entidad) {
        $this->entidad = $entidad;
    }
    public function getEntidad() {
        return $this->entidad;
    }
    public function crearQuery() {
        $stmt = "";
        if (count($this->errores) == 0) {
            if ($this->procesador->ejecutar()) {
                $agrupacion = '';
                $campos = $this->procesador->getSeleccion();
                if (count($campos) > 0) {
                    foreach ($campos as $key => $value) {
                        $campos[$key] = $this->nomEntidad . '.' . $value;
                    }
                    $campos = implode(', ', (count($campos) > 0) ? $campos : $this->campos);
                } else {
                    $campos = $this->nomEntidad;
                }
                $engine = new StringTemplate\Engine(':', '');
                $condicional = $this->procesarCondicional();
                if ($this->procesador->getBusqueda()) {
                    $busqueda = $this->procesador->getBusqueda();
                    $condicional = str_replace(":q", $busqueda['valor'], $condicional);
                }
                $orden = $this->procesarOrden();
                $sqlCount = $engine->render(
                        $this->template, [
                    'distinct' => 'DISTINCT',
                    'campos' => $this->nomEntidad,
                    'tabla' => $this->entidad . ' ' . $this->nomEntidad,
                    'condicionales' => $condicional,
                    'agrupacion' => $agrupacion,
                    'orden' => $orden,
                    'limites' => ''
                        ]
                );
                $sqlConCampos = $engine->render(
                        $this->template, [
                    'distinct' => 'DISTINCT',
                    'campos' => $campos,
                    'tabla' => $this->entidad . ' ' . $this->nomEntidad,
                    'condicionales' => $condicional,
                    'agrupacion' => $agrupacion,
                    'orden' => $orden,
                    'limites' => ''
                        ]
                );
                if ($this->condicionalForzado != null){
                    $sqlCount = str_replace('WHERE', 'WHERE ' . $this->condicionalForzado . ' AND ', $sqlCount);
                    $sqlConCampos = str_replace('WHERE', 'WHERE ' . $this->condicionalForzado . ' AND ', $sqlConCampos);
                }
                $queryCount = $this->manager->createQuery($sqlCount)
                        ->setFirstResult($this->procesador->getRegistrosPorPagina() * ($this->procesador->getPagina() - 1))
                        ->setMaxResults($this->procesador->getRegistrosPorPagina());
                $queryConCampos = $this->manager->createQuery($sqlConCampos)
                        ->setFirstResult($this->procesador->getRegistrosPorPagina() * ($this->procesador->getPagina() - 1))
                        ->setMaxResults($this->procesador->getRegistrosPorPagina());
                $resultsCount = new Paginator($queryCount, $fetchJoinCollection = true);
                $resultsConCampos = new Paginator($queryConCampos, $fetchJoinCollection = true);
                $results = $queryConCampos->getArrayResult();
                $totalItems = $resultsCount->count();
                if ($totalItems > 0) {     
                    $this->procesarPaginado($totalItems);
                } else {
                    $this->errores[] = "No se encontraron resultados";
                }
            } else {
                $this->errores = array_merge($this->errores, $this->procesador->getErrores());
            }
        }
        if (count($this->errores) > 0) {
            $totalItems = 0;
            $results = array('error' => $this->errores);
        }
        $result = new ApiResult();
        $result->setTotalRegistros($totalItems);
        $result->setRegistros($results);
        $result->setNumeroPaginas($this->numeroPaginas);
        $result->setPaginaActual($this->procesador->getPagina());
        return $result;
    }
    function procesarPaginado($totalRegistros) {
        $pagina = $this->procesador->getPagina();
        $registrosPorPagina = $this->procesador->getRegistrosPorPagina();
        $numeroPaginas = ceil($totalRegistros / $registrosPorPagina);
        if ($pagina > $numeroPaginas) {
            $this->errores[] = "El número de página proporcionado, excede la cantidad de páginas del conjunto de restultados";
        }
        $this->numeroPaginas = $numeroPaginas;
    }
    public function getNumeroPaginas() {
        return $this->numeroPaginas;
    }
    function procesarOrden() {
        $orden = '';
        foreach ($this->procesador->getOrden() as $campo => $direccion) {
            $orden .= $this->nomEntidad . ".$campo $direccion, ";
        }
        if ($orden != '') {
            $orden = ' ORDER BY ' . substr($orden, 0, -2);
        }
        return $orden;
    }
    public function setCampos($campos) {
        $this->campos = $campos;
        $this->procesador->setCamposSeleccionables($campos);
    }
    private $procesador;
    private $condicionalForzado;
    public function setCondicionalForzado($condicionalForzado) {
        $this->condicionalForzado = $condicionalForzado;
    }
    public function getCondicionalForzado() {
        return $this->condicionalForzado;
    }
    function procesarCondicional() {
        $condicional = '';
        foreach ($this->procesador->getFiltros() as $campo => $valor) {
            $filtros = $this->procesador->getFiltros();
            $parametros = $this->procesador->getParametros();
            switch ($parametros[$campo]['style']) {
                case 'flat':
                    $condicional .= "ent.$campo = '$filtros[$campo]' AND ";
                    break;
                case 'range':
                    if (count($valor) == 2)
                        $condicional .= 'ent.'.$campo . " BETWEEN '" . $filtros[$campo][0] . "' AND '" . $filtros[$campo][1] . "' AND ";
                    else
                        $condicional .= "ent.$campo = :$campo AND ";
                    break;
                case 'list':
                    if (count($valor) > 1) {
                        $valores = '';
                        foreach ($valor as $valor)
                            $valores .= $this->connection->quote($valor) . ', ';
                        $valores = substr($valores, 0, -2);
                        $condicional .= "ent.$campo IN ($valores) AND ";
                    } else
                        $condicional .= "ent.$campo = " . $this->connection->quote($valor) . " AND ";
                    break;
            }
        }
        foreach ($this->condicionales as $campo => $valor) {
            $condicional .= "$campo = " . $this->connection->quote($valor) . " AND ";
        }
        if ($condicional != '')
            $condicional = '(' . substr($condicional, 0, -4) . ')';
        $busqueda = $this->procesador->getBusqueda();
        if (!is_null($busqueda)) {
            $consulta = '';
            foreach ($busqueda['campos'] as $campo) {
                $consulta .= "ent.$campo LIKE '%:q%' OR ";
            }
            if ($consulta != '') {
                $consulta = '(' . substr($consulta, 0, -3) . ')';
                $condicional .= (($condicional != '') ? ' AND ' : '') . $consulta;
            }
        }
        if ($condicional != '')
            $condicional = "WHERE $condicional";
        return $condicional;
    }
    function asociarValores(Statement $stmt) {
        foreach ($this->procesador->getFiltros() as $campo => $valor) {
            $parametros = $this->procesador->getParametros();
            switch ($parametros[$campo]['style']) {
                case 'flat':
                    $stmt->bindValue($campo, $valor);
                    break;
                case 'range':
                    if (count($valor) == 2) {
                        $stmt->bindValue($campo . '_min', $valor[0]);
                        $stmt->bindValue($campo . '_max', $valor[1]);
                    } else
                        $stmt->bindValue($campo, $valor[0]);
                    break;
            }
        }
        if (!is_null($this->procesador->getBusqueda()))
            $stmt->bindValue('q', '%' . $this->procesador->getBusqueda()['valor'] . '%');
        return $stmt;
    }
    public function agregarCondicionales($condicionales) {
        $parametros = $this->procesador->getFiltros();
        foreach ($condicionales as $condicional) {
            if (array_key_exists($condicional['campo'], $parametros))
                $this->errores[] = 'El campo ' . $condicional['campo'] . ', es uno de los parametros del querystring.';
            else {
                if ($this->validarCampo($condicional['campo'], $condicional['valor'], $condicional['validaciones']))
                    $this->condicionales[$condicional['campo']] = $condicional['valor'];
            }
        }
    }
    function validarCampo($campo, $valor, $validaciones) {
        $validacion = new Collection([
            $campo => $validaciones
        ]);
        $data = [$campo => $valor];
        $error = $this->validator->validateValue($data, $validacion);
        if (count($error) > 0)
            $this->errores[] = $error[0]->getPropertyPath() . ':' . $error[0]->getMessage();
        return (count($error) == 0);
    }
    private $condicionEntidades = "";
    public function setCondicionales($condicionEntidades) {
        $this->condicionEntidades = $condicionEntidades;
    }
    public function crearQueryEntidades($campos, $entidades) {
        if (count($this->errores) == 0) {
            if ($this->procesador->ejecutar()) {
                $condicional = $this->procesarCondicional();
                if ($this->procesador->getBusqueda()) {
                    $busqueda = $this->procesador->getBusqueda();
                    $condicional = str_replace(":q", $busqueda['valor'], $condicional);
                }
                $cond = false;
                $this->condicionEntidades;
                if ($condicional == '' && $this->condicionEntidades != '') {
                    $condicional = " WHERE " . $this->condicionEntidades;
                    $cond = true;
                }
                if ($campos == '') {
                    $campos = $this->nomEntidad;
                }
                if ($entidades == '') {
                    $entidades = $this->getEntidad();
                }
                $orden = $this->procesarOrden();
                $agrupacion = '';
                $engine = new StringTemplate\Engine(':', '');
                $sqlStmt = $engine->render($this->template, ['distinct' => 'DISTINCT', 'campos' => $campos, 'tabla' => $entidades, 'condicionales' => $condicional, 'agrupacion' => $agrupacion, 'orden' => $orden, 'limites' => ''] );
                $sqlStmtEntity = $engine->render($this->template, ['distinct' => 'DISTINCT', 'campos' => 'ent', 'tabla' => $entidades, 'condicionales' => $condicional, 'agrupacion' => $agrupacion, 'orden' => $orden, 'limites' => ''] );
                if ($this->getCondicionalForzado() != null) {
                    $sqlStmt = str_replace('WHERE', 'WHERE ' . $this->getCondicionalForzado() . ' AND ', $sqlStmt);
                    $sqlStmtEntity = str_replace('WHERE', 'WHERE ' . $this->getCondicionalForzado() . ' AND ', $sqlStmtEntity);
                }
                if (!$cond && $this->condicionEntidades != '') {
                    $sqlStmt = str_replace('WHERE', 'WHERE ' . $this->condicionEntidades . ' AND ', $sqlStmt);
                    $sqlStmtEntity = str_replace('WHERE', 'WHERE ' . $this->condicionEntidades . ' AND ', $sqlStmtEntity);
                }
                $query = $this->manager->createQuery($sqlStmt)
                        ->setFirstResult($this->procesador->getRegistrosPorPagina() * ($this->procesador->getPagina() - 1))
                        ->setMaxResults($this->procesador->getRegistrosPorPagina());
                $queryEntity = $this->manager->createQuery($sqlStmtEntity)->setFirstResult($this->procesador->getRegistrosPorPagina() * ($this->procesador->getPagina() - 1))->setMaxResults($this->procesador->getRegistrosPorPagina());
                $results = $query->getArrayResult();
                $resultsCount = new Paginator($queryEntity, $fetchJoinCollection = true);
                $totalItems = $resultsCount->count();
                if ($totalItems > 0) {     
                    $this->procesarPaginado($totalItems);
                } else {
                    $this->errores[] = "No se encontraron resultados";
                }
            } else {
                $this->errores = array_merge($this->errores, $this->procesador->getErrores());
            }
        }
        if (count($this->errores) > 0) {
            $totalItems = 0;
            $results = array('error' => $this->errores);
        }
        $result = new ApiResult();
        $result->setNumeroPaginas($this->getNumeroPaginas());
        $result->setPaginaActual($this->procesador->getPagina());
        $result->setRegistros($results);
        $result->setTotalRegistros($totalItems);
        return $result;
    }
}
