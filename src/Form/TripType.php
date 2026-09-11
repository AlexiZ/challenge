<?php

namespace App\Form;

use App\Entity\Trip;
use App\Enum\TripModeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TripType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mode', EnumType::class, [
                'class' => TripModeEnum::class,
                'label' => 'Mode de déplacement',
                'expanded' => true,
                'choice_label' => fn (TripModeEnum $e) => $e->label(),
            ])
            ->add('distanceKm', NumberType::class, [
                'label' => 'Distance (km)',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'min' => 0.5,
                    'step' => 0.01,
                    'placeholder' => 'Ex: 12.53',
                ],
            ])
            ->add('tripDate', DateType::class, [
                'label' => 'Date du trajet',
                'widget' => 'single_text',
                'html5' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trip::class,
        ]);
    }
}
