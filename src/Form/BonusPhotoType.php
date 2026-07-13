<?php

namespace App\Form;

use App\Entity\BonusPhoto;
use App\Enum\BonusChallengeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class BonusPhotoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('challenge', EnumType::class, [
                'class'        => BonusChallengeEnum::class,
                'label'        => false,
                'choice_label' => fn (BonusChallengeEnum $e) => $e->label(),
                'expanded'     => true,
            ])
            ->add('photoFile', FileType::class, [
                'label' => 'Photo',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new NotNull(message: 'Veuillez sélectionner une photo.'),
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Format accepté : JPG, PNG, WebP.',
                    ]),
                ],
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Commentaire (optionnel)',
                'required' => false,
                'attr' => ['rows' => 3, 'maxlength' => 500],
            ])
            ->add('consentToPublish', CheckboxType::class, [
                'label' => 'J\'autorise la publication de ma photo sur la page publique du challenge',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BonusPhoto::class,
        ]);
    }
}
