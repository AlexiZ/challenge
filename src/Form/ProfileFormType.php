<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\BikeTypeEnum;
use App\Enum\CyclistProfileEnum;
use App\Enum\GenderEnum;
use App\Enum\MainBarrierEnum;
use App\Enum\PerceivedBenefitEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('avatarFile', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Format accepté : JPG, PNG, WebP.',
                    ]),
                ],
            ])
            ->add('firstName', TextType::class, ['label' => 'Prénom'])
            ->add('lastName', TextType::class, ['label' => 'Nom'])
            ->add('username', TextType::class, ['label' => 'Pseudo'])
            ->add('cyclistProfile', EnumType::class, [
                'class' => CyclistProfileEnum::class,
                'label' => 'Profil cycliste',
                'required' => false,
                'placeholder' => 'Non précisé',
                'choice_label' => fn (CyclistProfileEnum $e) => $e->label(),
            ])
            ->add('bikeType', EnumType::class, [
                'class' => BikeTypeEnum::class,
                'label' => 'Type de vélo',
                'required' => false,
                'placeholder' => 'Non précisé',
                'choice_label' => fn (BikeTypeEnum $e) => $e->label(),
            ])
            ->add('gender', EnumType::class, [
                'class' => GenderEnum::class,
                'label' => 'Genre',
                'required' => false,
                'placeholder' => 'Non précisé',
                'choice_label' => fn (GenderEnum $e) => $e->label(),
            ])
            ->add('age', IntegerType::class, ['label' => 'Âge', 'required' => false])
            ->add('perceivedBenefit', EnumType::class, [
                'class' => PerceivedBenefitEnum::class,
                'label' => 'Principal bénéfice',
                'required' => false,
                'placeholder' => 'Non précisé',
                'choice_label' => fn (PerceivedBenefitEnum $e) => $e->label(),
            ])
            ->add('mainBarrier', EnumType::class, [
                'class' => MainBarrierEnum::class,
                'label' => 'Principal frein',
                'required' => false,
                'placeholder' => 'Non précisé',
                'choice_label' => fn (MainBarrierEnum $e) => $e->label(),
            ])
            ->add('receiveEmails', CheckboxType::class, [
                'label' => 'Recevoir les communications',
                'required' => false,
            ])
            ->add('publicProfile', CheckboxType::class, [
                'label' => 'Profil public',
                'required' => false,
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'first_options' => ['label' => 'Nouveau mot de passe'],
                'second_options' => ['label' => 'Confirmer le mot de passe'],
                'constraints' => [
                    new Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
