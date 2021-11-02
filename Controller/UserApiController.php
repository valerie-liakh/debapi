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
        $datos = $this->container->get('api.respuestas')->Contenido($data, 10, 2);
        return $datos;
    }
    public function getUsersAction() {
        $camposOrdenables = array('id','username','name','is_active','datetime_last_conection');
        $camposSeleccionables = array('name', 'username', 'id', 'is_active', 'datetime_last_conection');
        $data = $this->container->get('user.api.handler')->getAll($camposOrdenables, $camposSeleccionables);
        return $this->container->get('api.respuestas')->Contenido($data->getRegistros(), $data->getTotalRegistros(), $data->getNumeroPaginas(), $data->getPaginaActual() );
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
