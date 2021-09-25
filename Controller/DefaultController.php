<?php
namespace Lynx\ApiBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('LynxApiBundle:Default:index.html.twig');
    }
}
