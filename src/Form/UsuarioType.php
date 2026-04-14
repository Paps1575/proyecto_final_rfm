<?php

namespace App\Form;

use App\Entity\Perfil;
use App\Entity\Usuario;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UsuarioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = $options['is_new'];

        $builder
            ->add('strNombreUsuario', TextType::class, [
                'label' => 'Nombre de Usuario / Login',
                'attr' => ['class' => 'form-control rounded-3', 'placeholder' => 'Ej: ronaldinho10']
            ])
            ->add('strPwd', PasswordType::class, [
                'label' => $isNew ? 'Contraseña' : 'Cambiar Contraseña',
                'mapped' => false,
                'required' => $isNew,
                'attr' => [
                    'class' => 'form-control rounded-3',
                    'placeholder' => $isNew ? 'Mínimo 6 caracteres' : 'Dejar vacío para no cambiar'
                ],
                'constraints' => $this->getPasswordConstraints($isNew),
            ])
            ->add('strCorreo', EmailType::class, [
                'label' => 'Correo Electrónico',
                'attr' => ['class' => 'form-control rounded-3']
            ])
            ->add('strNumeroCelular', TextType::class, [
                'label' => 'Teléfono / WhatsApp',
                'attr' => ['class' => 'form-control rounded-3', 'maxlength' => '10']
            ])
            ->add('foto', FileType::class, [
                'label' => 'Foto de Perfil',
                'mapped' => false, // SE QUEDA FALSE: El controlador hará el trabajo sucio
                'required' => false,
                'attr' => [
                    'class' => 'form-control rounded-3',
                    'accept' => 'image/jpeg,image/png' // Ayuda al navegador a filtrar
                ],
                'constraints' => [
                    new File(
                        maxSize: '2M',
                        mimeTypes: ['image/jpeg', 'image/png'],
                        mimeTypesMessage: 'Sube una imagen válida (JPG o PNG) de máximo 2MB'
                    )
                ],
            ])
            ->add('perfil', EntityType::class, [
                'class' => Perfil::class,
                'choice_label' => 'strNombrePerfil',
                'label' => 'Perfil de Accesos (Matriz)',
                'attr' => ['class' => 'form-select rounded-3']
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Roles de Seguridad',
                'choices'  => [
                    'Administrador (Todo)' => 'ROLE_ADMIN',
                    'Usuario (Limitado)' => 'ROLE_USER',
                ],
                'expanded' => true,
                'multiple' => true,
                'attr' => ['class' => 'd-flex gap-3 mt-2']
            ])
            ->add('idEstadoUsuario', ChoiceType::class, [
                'label' => 'Estado de la Cuenta',
                'choices' => [
                    'ACTIVO' => true,
                    'BLOQUEADO' => false,
                ],
                'attr' => ['class' => 'form-select rounded-3']
            ])
        ;
    }

    private function getPasswordConstraints(bool $isNew): array
    {
        $constraints = [];
        if ($isNew) {
            $constraints[] = new NotBlank([
                'message' => 'La contraseña es obligatoria para nuevos registros.',
            ]);
        }
        $constraints[] = new Length([
            'min' => 6,
            'minMessage' => 'La contraseña debe tener al menos {{ limit }} caracteres.',
            'max' => 4096,
        ]);
        return $constraints;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Usuario::class,
            'is_new' => true,
        ]);
        $resolver->setAllowedTypes('is_new', 'bool');
    }
}
