<?php
namespace Lynx\ApiBundle\Services;
use Doctrine\Common\Persistence\ObjectManager;
use StringTemplate;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Lynx\ApiBundle\Components\ApiResult;
use Symfony\Component\Validator\Constraints\Collection;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Statement;
class QueryBuilder
{
    private $connection;
    private $template = "SELECT :distinct :campos FROM :tabla :condicionales :agrupacion :orden :limites";
    private $condicionales = [];
    private $validator;
    private $numeroPaginas=0;
    private $nomEntidad = 'ent';
    private $campos = [];
    private $manager;
    function __construct(ProcesadorQuerystring $procesador, Connection $connection, $validator, ObjectManager $om)
    {
        $this->procesador = $procesador;
        $this->connection = $connection;
        $this->validator = $validator;
        $this->manager = $om;
    }
    private $errores = [];
    public function getErrores()
    {
        return $this->errores;
    }
    public function getProcesador()
    {
        return $this->procesador;
    }
    private $entidad;
    public function setEntidad($entidad)
    {
        $this->entidad = $entidad;
    }
    public function crearQuery()
    {
        $stmt = "";
        if(count($this->errores)==0) {
            if ($this->procesador->ejecutar()) {
                $agrupacion = '';
                $campos = $this->procesador->getSeleccion();
                $campos = implode(',', (count($campos) > 0) ? $campos : $this->campos);
                $campos = 'ent';
                $engine = new StringTemplate\Engine(':', '');
                $condicional = $this->procesarCondicional();
                $orden = $this->procesarOrden();
                $sql = $engine->render(
                    $this->template,
                    [
                        'distinct' => 'DISTINCT',
                        'campos' => $campos,
                        'tabla' => $this->entidad.' '.$this->nomEntidad,
                        'condicionales' => $condicional,
                        'agrupacion' => $agrupacion,
                        'orden' => $orden,
                        'limites' => ''
                    ]
                );
                if($this->condicionalForzado!= null)
                    $sqlCount = str_replace('WHERE', 'WHERE '.$this->condicionalForzado.' AND ',$sql);
                $query = $this->manager->createQuery($sql)
                        ->setFirstResult($this->procesador->getRegistrosPorPagina() * ($this->procesador->getPagina() - 1))
                        ->setMaxResults($this->procesador->getRegistrosPorPagina());
                $resultsCount = new Paginator($query, $fetchJoinCollection = true);
                $results = $query->getArrayResult();
                $totalItems = $resultsCount->count();
                $this->procesarPaginado($totalItems);
            } else {
                $this->errores = array_merge($this->errores, $this->procesador->getErrores());
                return false;
            }
        }
        else
            return false;
        $result = new ApiResult();
        $result->setTotalRegistros($totalItems);
        $result->setRegistros($results);
        $result->setNumeroPaginas($this->numeroPaginas);
        $result->setPaginaActual($this->procesador->getPagina());
        return $result;
    }
    function procesarPaginado($totalRegistros){
        $pagina = $this->procesador->getPagina();
        $registrosPorPagina = $this->procesador->getRegistrosPorPagina();
        $numeroPaginas = ceil($totalRegistros / $registrosPorPagina);
        if($pagina > $numeroPaginas) {
            $this->errores[] = "El número de página proporcionado, excede la cantidad de páginas del conjunto de restultados";
            return false;
        }
        $this->numeroPaginas = $numeroPaginas;
    }
    function procesarOrden()
    {
        $orden = '';
        foreach($this->procesador->getOrden() as $campo => $direccion)
        {
            $orden .= $this->nomEntidad.".$campo $direccion, ";
        }
        if($orden!=''){
            $orden = ' ORDER BY '.substr($orden,0,-2);
        }
        return $orden;
    }
    public function setCampos($campos)
    {
        $this->campos = $campos;
        $this->procesador->setCamposSeleccionables($campos);
    }
    private $distinct = true;
    public function setDistinct($distinct)
    {
        $this->distinct = $distinct;
    }
    private $procesador;
    private $condicionalForzado;
    public function setCondicionalForzado($condicionalForzado)
    {
        $this->condicionalForzado = $condicionalForzado;
    }
    function procesarCondicional()
    {
        $condicional = '';
        foreach($this->procesador->getFiltros() as $campo => $valor)
        {
            $parametros = $this->procesador->getParametros();
            switch($parametros[$campo]['style'])
            {
                case 'flat':
                    $condicional .= "$campo = :$campo AND ";
                    break;
                case 'range':
                    if(count($valor)==2)
                        $condicional .= $campo.' BETWEEN :'.$campo.'_min AND :'.$campo.'_max AND ';
                    else
                        $condicional .= "$campo = :$campo AND ";
                    break;
                case 'list':
                    if(count($valor)>1) {
                        $valores = '';
                        foreach($valor as $valor)
                            $valores .= $this->connection->quote($valor).', ';
                        $valores = substr($valores, 0, -2);
                        $condicional .= "$campo IN ($valores) AND ";
                    }else
                        $condicional .= "$campo = ".$this->connection->quote($valor)." AND ";
                    break;
            }
        }
        foreach($this->condicionales as $campo => $valor)
        {
            $condicional .= "$campo = ".$this->connection->quote($valor)." AND ";
        }
        if($condicional!='')
            $condicional = '('.substr($condicional,0,-4).')';
        $busqueda = $this->procesador->getBusqueda();
        if(!is_null($busqueda))
        {
            $consulta = '';
            foreach($busqueda['campos'] as $campo)
            {
                $consulta .= "$campo LIKE :q OR ";
            }
            if($consulta!='')
            {
                $consulta = '(' . substr($consulta, 0, -3) . ')';
                $condicional .= (($condicional != '') ? ' AND ' : '') . $consulta;
            }
        }
        if($condicional!='')
            $condicional = "WHERE $condicional";
        return $condicional;
    }
    function asociarValores(Statement $stmt){
        foreach($this->procesador->getFiltros() as $campo => $valor)
        {
            $parametros = $this->procesador->getParametros();
            switch($parametros[$campo]['style'])
            {
                case 'flat':
                    $stmt->bindValue($campo, $valor);
                    break;
                case 'range':
                    if(count($valor)==2) {
                        $stmt->bindValue($campo . '_min', $valor[0]);
                        $stmt->bindValue($campo . '_max', $valor[1]);
                    } else
                        $stmt->bindValue($campo, $valor[0]);
                    break;
            }
        }
        if(!is_null($this->procesador->getBusqueda()))
            $stmt->bindValue('q', '%'.$this->procesador->getBusqueda()['valor'].'%');
        return $stmt;
    }
    public function agregarCondicionales($condicionales)
    {
        $parametros = $this->procesador->getFiltros();
        foreach($condicionales as $condicional)
        {
            if(array_key_exists($condicional['campo'],$parametros))
                $this->errores[] = 'El campo '.$condicional['campo'].', es uno de los parametros del querystring.';
            else {
                if ($this->validarCampo($condicional['campo'], $condicional['valor'], $condicional['validaciones']))
                    $this->condicionales[$condicional['campo']] = $condicional['valor'];
            }
        }
    }
    function validarCampo($campo, $valor, $validaciones)
    {
        $validacion = new Collection([
                $campo => $validaciones
            ]);
        $data = [$campo=>$valor];
        $error = $this->validator->validateValue($data, $validacion);
        if(count($error)>0)
            $this->errores[] = $error[0]->getPropertyPath().':'.$error[0]->getMessage();
        return (count($error)==0);
    }
}
