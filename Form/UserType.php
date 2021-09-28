<?php
namespace Lynx\ApiBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
class UserType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder        ->add('name', TextType::class, array('label' => 'Nombre'))
                        ->add('username', TextType::class, array('label' => 'Usuario',
                            'attr' => array('maxlength' => '25', 'help' => 'Máximo 25 carácteres')))
                        ->add('password', TextType::class, array('label' => 'Contraseña',
                            'attr' => array('maxlength' => '8', 'help' => 'Máximo 8 carácteres')))
                        ->add('email', TextType::class, array('label' => 'Correo'))
                        ->add('isActive', TextType::class, array('choices' => array('Desactivado' => '0', 'Activado' => '1'),
                            'label' => 'Estado'))
        ;
    }
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'LynxBundle\Entity\User',
            'csrf_protection' => false,
        ));
    }
    public function getBlockPrefix() {
        return null;
    }
}
