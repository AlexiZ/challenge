<?php

namespace App\Form;

use App\Entity\CityEdition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

/**
 * Configuration d'une participation par un admin ville ou global.
 * Pendant l'édition, seuls les seuils de distance suspecte restent modifiables
 * (les champs "disabled" sont ignorés à la soumission par le composant Form).
 */
class CityAdminConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $locked = $options['edition_locked'];

        $builder
            ->add('registrationsOpen', CheckboxType::class, ['label' => 'Inscriptions ouvertes', 'required' => false, 'disabled' => $locked])
            ->add('tripsEntryOpen', CheckboxType::class, ['label' => 'Saisie des trajets ouverte', 'required' => false, 'disabled' => $locked])
            ->add('photoChallengesEnabled', CheckboxType::class, ['label' => 'Défis photo activés', 'required' => false, 'disabled' => $locked])
            ->add('rankingsPublic', CheckboxType::class, ['label' => 'Classements publics', 'required' => false, 'disabled' => $locked])
            ->add('bonusPhotoConfigs', CollectionType::class, [
                'entry_type' => BonusPhotoConfigType::class,
                'label' => false,
                'disabled' => $locked,
            ])
            ->add('suspiciousDistanceBike', NumberType::class, ['label' => 'Distance suspecte vélo (km)', 'scale' => 0, 'constraints' => [new PositiveOrZero()]])
            ->add('suspiciousDistanceWalk', NumberType::class, ['label' => 'Distance suspecte marche (km)', 'scale' => 0, 'constraints' => [new PositiveOrZero()]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CityEdition::class,
            'edition_locked' => false,
        ]);
        $resolver->setAllowedTypes('edition_locked', 'bool');
    }
}
