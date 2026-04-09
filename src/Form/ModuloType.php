<?php

namespace App\Form;

use App\Entity\Modulo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ModuloType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('strNombre', TextType::class, [
                'label' => 'Nombre del Módulo',
                'attr' => ['placeholder' => 'Ej: SOPAS, VENTAS, REPORTES']
            ])
            ->add('strRuta', TextType::class, [
                'label' => 'Ruta del Sistema (Route Name)',
                'attr' => ['placeholder' => 'Ej: app_sopas_index'],
                'help' => 'Debe coincidir con el nombre definido en el controlador.'
            ])
            ->add('intEstado', ChoiceType::class, [
                'label' => 'Estado Inicial',
                'choices' => [
                    'Activo (Visible)' => 1,
                    'Inactivo (Oculto)' => 0,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Modulo::class,
        ]);
    }
}
