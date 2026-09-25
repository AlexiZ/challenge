<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Team;
use App\Entity\User;
use App\Enum\BikeTypeEnum;
use App\Enum\CyclistProfileEnum;
use App\Enum\GenderEnum;
use App\Enum\MainBarrierEnum;
use App\Enum\PerceivedBenefitEnum;
use App\Repository\TeamRepository;
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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\NotNull;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, ['label' => 'Prénom', 'required' => false, 'empty_data' => ''])
            ->add('lastName', TextType::class, ['label' => 'Nom', 'required' => false, 'empty_data' => ''])
            ->add('username', TextType::class, [
                'label' => 'Pseudo',
                'required' => false,
                'empty_data' => '',
                'help' => 'Lettres minuscules, chiffres, points, tirets, underscores uniquement. Généré depuis votre email si vide.',
            ])
            ->add('email', EmailType::class, ['label' => 'Email', 'empty_data' => '', 'required' => true])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'choice_label' => function (City $city): string {
                    return $city->getDisplayName();
                },
                // Teams per city, read by assets/js/registration-teams.js to refresh the team list client-side
                'choice_attr' => fn (City $city): array => ['data-teams' => json_encode(
                    $city->getTeams()->map(fn (Team $team) => ['id' => $team->getId(), 'name' => $team->getName()])->getValues()
                )],
                'label' => 'Ma ville',
                'placeholder' => 'Choisir une ville...',
                'required' => true,
                'constraints' => [
                    new NotNull(message: 'Veuillez choisir votre ville.'),
                ],
            ])
            ->add('cyclistProfile', EnumType::class, [
                'class' => CyclistProfileEnum::class,
                'label' => 'Mon profil cycliste',
                'required' => false,
                'help' => 'À quelle fréquence utilisez-vous votre vélo ?',
                'placeholder' => '',
                'choice_label' => fn (CyclistProfileEnum $e) => $e->label(),
            ])
            ->add('bikeType', EnumType::class, [
                'class' => BikeTypeEnum::class,
                'label' => 'Type de vélo',
                'required' => false,
                'placeholder' => '',
                'choice_label' => fn (BikeTypeEnum $e) => $e->label(),
            ])
            ->add('gender', EnumType::class, [
                'class' => GenderEnum::class,
                'label' => 'Genre',
                'required' => false,
                'placeholder' => '',
                'choice_label' => fn (GenderEnum $e) => $e->label(),
            ])
            ->add('age', IntegerType::class, ['label' => 'Âge', 'required' => false])
            ->add('perceivedBenefit', EnumType::class, [
                'class' => PerceivedBenefitEnum::class,
                'label' => 'Principal bénéfice perçu',
                'required' => false,
                'help' => 'Ce que le vélo vous apporte avant tout au quotidien.',
                'placeholder' => '',
                'choice_label' => fn (PerceivedBenefitEnum $e) => $e->label(),
            ])
            ->add('mainBarrier', EnumType::class, [
                'class' => MainBarrierEnum::class,
                'label' => 'Principal frein',
                'required' => false,
                'help' => 'Ce qui vous empêche le plus souvent de prendre le vélo.',
                'placeholder' => '',
                'choice_label' => fn (MainBarrierEnum $e) => $e->label(),
            ])
            ->add('receiveEmails', CheckboxType::class, [
                'label' => 'Je souhaite recevoir les communications du challenge',
                'required' => false,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confirmer le mot de passe'],
                'constraints' => [
                    new Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'),
                    new NotCompromisedPassword(skipOnError: true),
                ],
            ]);

        // Team choices depend on the chosen city: rebuilt on initial data and after the city is submitted
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $this->addTeamsField($event->getForm(), $event->getData()?->getCity());
        });
        $builder->get('city')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $this->addTeamsField($event->getForm()->getParent(), $event->getForm()->getData());
        });
    }

    private function addTeamsField(FormInterface $form, ?City $city): void
    {
        $form->add('teams', EntityType::class, [
            'class' => Team::class,
            'choice_label' => 'name',
            'query_builder' => fn (TeamRepository $repository) => $repository->createQueryBuilder('t')
                ->where('t.city = :city')
                ->setParameter('city', $city)
                ->orderBy('t.name', 'ASC'),
            'label' => 'Mes équipes',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'mapped' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
