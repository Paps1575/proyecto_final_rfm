<?php

namespace App\Form;

use App\Entity\PermisoPerfil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PermisoPerfilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('boolConsultar', CheckboxType::class, [
                'label' => 'Ver',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('boolAgregar', CheckboxType::class, [
                'label' => 'Crear',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('boolEditar', CheckboxType::class, [
                'label' => 'Editar',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('boolEliminar', CheckboxType::class, [
                'label' => 'Borrar',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PermisoPerfil::class,
        ]);
    }
}
