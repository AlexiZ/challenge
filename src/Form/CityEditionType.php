<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\CityEdition;
use App\Entity\Edition;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CityEditionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('city', EntityType::class, [
                'class' => City::class,
                'choice_label' => 'name',
                'label' => 'Ville',
            ])
            ->add('edition', EntityType::class, [
                'class' => Edition::class,
                'choice_label' => fn (Edition $e) => (string) $e,
                'label' => 'Édition',
            ])
            ->add('targetParticipants', IntegerType::class, ['label' => 'Objectif participants'])
            ->add('targetDistanceKm', IntegerType::class, ['label' => 'Objectif km'])
            ->add('pointsPerDay', NumberType::class, ['label' => 'Points / jour actif', 'scale' => 2])
            ->add('pointsPerKmBike', NumberType::class, ['label' => 'Points / km vélo', 'scale' => 2])
            ->add('pointsPerKmWalk', NumberType::class, ['label' => 'Points / km marche', 'scale' => 2])
            ->add('suspiciousDistanceBike', NumberType::class, ['label' => 'Distance suspecte vélo (km)', 'scale' => 0])
            ->add('suspiciousDistanceWalk', NumberType::class, ['label' => 'Distance suspecte marche (km)', 'scale' => 0])
            ->add('registrationsOpen', CheckboxType::class, ['label' => 'Inscriptions ouvertes', 'required' => false])
            ->add('tripsEntryOpen', CheckboxType::class, ['label' => 'Saisie trajets ouverte', 'required' => false])
            ->add('photoChallengesEnabled', CheckboxType::class, ['label' => 'Défis photo activés', 'required' => false])
            ->add('rankingsPublic', CheckboxType::class, ['label' => 'Classements publics', 'required' => false])
            ->add('profilesPublic', CheckboxType::class, ['label' => 'Profils publics', 'required' => false])
            ->add('organizerMessage', TextareaType::class, [
                'label' => 'Message de l\'organisateur',
                'required' => false,
                'attr' => ['rows' => 5],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CityEdition::class,
        ]);
    }
}
