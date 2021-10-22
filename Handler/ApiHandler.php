<?php
namespace Lynx\ApiBundle\Handler;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Lynx\ApiBundle\Exception\InvalidFormException;
class ApiHandler implements ApiHandlerInterface {
    private $om;
    private $entityClass;
    private $repository;
    private $formFactory;
    private $typeClass;
    public function __construct(ObjectManager $om, $entityClass, FormFactoryInterface $formFactory, $typeClass) {
        $this->om = $om;
        $this->entityClass = $entityClass;
        $this->repository = $this->om->getRepository($this->entityClass);
        $this->formFactory = $formFactory;
        $this->typeClass = $typeClass;
    }
    public function get($id) {
        return $this->repository->find($id);
    }
    public function getAll() {
        return $this->repository->findAll();
    }
    public function post(array $parameters) {
        $page = new $this->entityClass();
        return $this->processForm($page, $parameters, 'POST');
    }
    private function processForm($page, array $parameters, $method = "PUT")
    {   
        $text = new $this->typeClass;
        $form = $this->formFactory->create($this->typeClass, $page, array('method' => $method));
        $form->submit($parameters, 'PATCH' !== $method);
        if ($form->isValid()) {
            $page = $form->getData();
            $this->om->persist($page);
            $this->om->flush($page);
            return $page;
        }
        throw new InvalidFormException('Invalid submitted data', $form);
    }
}
