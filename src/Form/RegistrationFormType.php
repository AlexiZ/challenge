<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\User;
use App\Enum\BikeTypeEnum;
use App\Enum\CyclistProfileEnum;
use App\Enum\GenderEnum;
use App\Enum\MainBarrierEnum;
use App\Enum\PerceivedBenefitEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, ['label' => 'Prénom'])
            ->add('lastName', TextType::class, ['label' => 'Nom'])
            ->add('username', TextType::class, [
                'label' => 'Pseudo',
                'help' => 'Lettres minuscules, chiffres, points, tirets, underscores uniquement.',
            ])
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'choice_label' => function (City $city): string {
                    return $city->getDisplayName();
                },
                'label' => 'Ma ville',
                'placeholder' => 'Choisir une ville...',
            ])
            ->add('cyclistProfile', EnumType::class, [
                'class' => CyclistProfileEnum::class,
                'label' => 'Mon profil cycliste',
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
                'label' => 'Principal bénéfice perçu',
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
                'label' => 'Je souhaite recevoir les communications du challenge',
                'required' => false,
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'J\'accepte les conditions d\'utilisation',
                'mapped' => false,
                'constraints' => [
                    new IsTrue(message: 'Vous devez accepter les conditions d\'utilisation.'),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confirmer le mot de passe'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer un mot de passe.'),
                    new Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'),
                    new NotCompromisedPassword(skipOnError: true),
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
