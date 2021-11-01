<?php
namespace Lynx\ApiBundle\Components;
class ApiResult {
    private $totalRegistros = 0;
    public function getRegistros()
    {
        return $this->registros;
    }
    public function setRegistros($registros)
    {
        $this->registros = $registros;
    }
    private $registros;
    public function getTotalRegistros()
    {
        return $this->totalRegistros;
    }
    public function setTotalRegistros($totalRegistros)
    {
        $this->totalRegistros = $totalRegistros;
    }
    private $numeroPaginas;
    public function getNumeroPaginas()
    {
        return $this->numeroPaginas;
    }
    public function setNumeroPaginas($numeroPaginas)
    {
        $this->numeroPaginas = $numeroPaginas;
    }
    private $paginaActual;
    public function getPaginaActual()
    {
        return $this->paginaActual;
    }
    public function setPaginaActual($paginaActual)
    {
        $this->paginaActual = $paginaActual;
    }
} 
