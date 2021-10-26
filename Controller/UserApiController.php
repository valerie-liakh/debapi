<?php
namespace Lynx\ApiBundle\Controller;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\Annotations\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\FormTypeInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Lynx\ApiBundle\Form\UserType;
use Lynx\ApiBundle\Exception\InvalidFormException;
class UserApiController extends FOSRestController {
    public function getUserAction($id) {
        if (!($data = $this->container->get('user.api.handler')->get($id))) {
            throw new NotFoundHttpException(sprintf('The resource \'%s\' was not found.', $id));
        }
        $qb = $this->container->get('query_builder');
        $procesador = $qb->getProcesador();
        $qb->setCampos(['name', 'username']);
        $procesador->setCamposConsultables(['name']);
        $procesador->setCamposOrdenables(['name', 'username']);
        $resultado = $qb->crearQuery();
        if (!$resultado){
             throw new NotFoundHttpException(sprintf('The resource \'%s\' was not found.', $id));
        }
        if ($resultado->getTotalRegistros() == 0){
            throw new NotFoundHttpException(sprintf('The resource \'%s\' was not found.', $id));
        }
        $datos = $this->container->get('api.respuestas')->Contenido($data, 10, 2);
        return $datos;
    }
    public function getUsersAction() {
        $data = $this->container->get('user.api.handler')->getAll();
        return $this->container->get('api.respuestas')->Contenido($data, 10, 2);
    }
    public function postUserAction(Request $request) {
        try {
            $newPage = $this->container->get('user.api.handler')->post(
                    $request->request->all()
            );
            $routeOptions = array(
                'id' => $newPage->getId(),
                'msg' => 'success'
            );
            return $routeOptions;
        } catch (InvalidFormException $exception) {
            return $exception->getForm();
        }
    }
}
