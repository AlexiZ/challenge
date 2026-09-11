<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('firstName', TextType::class, ['label' => 'Prénom'])
            ->add('lastName', TextType::class, ['label' => 'Nom'])
            ->add('username', TextType::class, ['label' => 'Pseudo'])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'choice_label' => 'name',
                'label' => 'Ville',
                'required' => false,
                'placeholder' => 'Aucune (super-admin)',
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôles supplémentaires',
                'choices' => [
                    'Admin ville' => 'ROLE_ADMIN_CITY',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'Mot de passe',
                'required' => $options['require_password'],
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => $options['require_password'] ? [
                    new NotBlank(message: 'Le mot de passe est obligatoire.'),
                    new Length(min: 8, minMessage: 'Minimum {{ limit }} caractères.'),
                    new NotCompromisedPassword(skipOnError: true),
                ] : [
                    new Length(min: 8, minMessage: 'Minimum {{ limit }} caractères.'),
                    new NotCompromisedPassword(skipOnError: true),
                ],
                'help' => $options['require_password'] ? null : 'Laisser vide pour ne pas modifier.',
            ])
            ->add('receiveEmails', CheckboxType::class, ['label' => 'Recevoir les emails', 'required' => false])
            ->add('publicProfile', CheckboxType::class, ['label' => 'Profil public', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'require_password' => false,
        ]);
        $resolver->setAllowedTypes('require_password', 'bool');
    }
}
