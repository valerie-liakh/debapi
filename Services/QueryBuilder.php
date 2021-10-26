<?php
namespace Lynx\ApiBundle\Services;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Statement;
use Inmobilia\APIBundle\Components\ApiResult;
use MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass;
use StringTemplate;
use Symfony\Component\Validator\Constraints\Collection;
class QueryBuilder {
    private $connection;
    private $template = "SELECT :campos FROM :tabla :condicionales :agrupacion :orden";
    private $condicionales = [];
    private $validator;
    private $numeroPaginas = 0;
    private $campos = [];
    public function setCampos($campos) {
        $this->campos = $campos;
        $this->procesador->setCamposSeleccionables($campos);
    }
    private $procesador;
    public function getProcesador() {
        return $this->procesador;
    }
    function __construct(ProcesadorQuerystring $procesador, Connection $connection, $validator) {
        $this->procesador = $procesador;
        $this->connection = $connection;
        $this->validator = $validator;
    }
    public function crearQuery() {
        $stmt = "";
        if (count($this->errores) == 0) {
            if ($this->procesador->ejecutar()) {
                $campos = $this->procesador->getSeleccion();
                $campos = implode(',', (count($campos) > 0) ? $campos : $this->campos);
                $engine = new StringTemplate\Engine(':', '');
                if ($this->procesador->conConteo()) {
                    $agrupacion = " GROUP BY $campos";
                    $campos .= ',count(*) nro_inmuebles';
                }
                $condicional = $this->procesarCondicional();
                $orden = $this->procesarOrden();
                $sqlCount = 'SELECT COUNT(*) FROM (' . $engine->render(
                                $this->template, [
                            'campos' => $campos,
                            'tabla' => $this->tabla,
                            'condicionales' => $condicional,
                            'agrupacion' => $agrupacion,
                            'orden' => $orden
                                ]
                        ) . ') T1';
                $stmt = $this->connection->prepare($sqlCount);
                $stmt = $this->asociarValores($stmt);
                $stmt->execute();
                $total = $stmt->fetchColumn();
                $limite = $this->procesarPaginado($total);
                if ($limite) {
                    $sql = $engine->render(
                            $this->template, [
                        'distinct' => ($this->distinct) ? 'DISTINCT' : '',
                        'campos' => $campos,
                        'tabla' => $this->tabla,
                        'condicionales' => $condicional,
                        'agrupacion' => $agrupacion,
                        'orden' => $orden,
                        'limites' => $limite
                            ]
                    );
                    $stmt = $this->connection->prepare($sql);
                    $stmt = $this->asociarValores($stmt);
                    $stmt->execute();
                } else
                    return false;
            } else {
                $this->errores = array_merge($this->errores, $this->procesador->getErrores());
                return false;
            }
        } else{
            return false;
        }
        $result = new ApiResult();
        $result->setTotalRegistros($total);
        $result->setRegistros($stmt->fetchAll());
        $result->setNumeroPaginas($this->numeroPaginas);
        return $result;
    }
    function procesarPaginado($totalRegistros) {
        $pagina = $this->procesador->getPagina();
        $registrosPorPagina = $this->procesador->getRegistrosPorPagina();
        $numeroPaginas = ceil($totalRegistros / $registrosPorPagina);
        if ($pagina > $numeroPaginas) {
            $this->errores[] = "El numero de página proporcionado, exece la cantidad de paginas del conjunto de restultados";
            return false;
        }
        $this->numeroPaginas = $numeroPaginas;
        $inicio = ($pagina - 1) * $registrosPorPagina;
        return "LIMIT $inicio, $registrosPorPagina";
    }
}
