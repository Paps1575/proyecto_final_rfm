<?php

namespace App\Form;

use App\Entity\Perfil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PerfilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('strNombrePerfil', TextType::class, [
                'label' => 'Nombre del Perfil',
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Ej. VENDEDOR MAYORISTA',
                    'style' => 'text-transform: uppercase;'
                ]
            ])
            ->add('bitAdministrador', CheckboxType::class, [
                'label' => '¿Activar Privilegios de Administrador Total?',
                'help' => 'Si marcas esto, el perfil ignorará las restricciones de la matriz.',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Perfil::class]);
    }
}
