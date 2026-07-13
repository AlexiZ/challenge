<?php

namespace App\Form;

use App\Entity\City;
use App\Enum\SocialLinkTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom de la ville']);

        if ($options['show_slug']) {
            $builder->add('slug', TextType::class, [
                'label' => 'Slug (URL)',
                'required' => false,
                'help' => 'Généré automatiquement si laissé vide. Ex: saint-brieuc',
            ]);
        }

        $builder
            ->add('organizationName', TextType::class, ['label' => 'Nom de l\'organisme', 'required' => false])
            ->add('organizationDescription', TextareaType::class, [
                'label' => 'Description de l\'organisme',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('contactEmail', EmailType::class, ['label' => 'Email de contact', 'required' => false])
            ->add('socialLink1Type', EnumType::class, [
                'class' => SocialLinkTypeEnum::class,
                'label' => 'Réseau social 1',
                'required' => false,
                'placeholder' => 'Aucun',
                'choice_label' => fn (SocialLinkTypeEnum $e) => $e->label(),
            ])
            ->add('socialLink1Url', UrlType::class, ['label' => 'URL réseau social 1', 'required' => false])
            ->add('socialLink2Type', EnumType::class, [
                'class' => SocialLinkTypeEnum::class,
                'label' => 'Réseau social 2',
                'required' => false,
                'placeholder' => 'Aucun',
                'choice_label' => fn (SocialLinkTypeEnum $e) => $e->label(),
            ])
            ->add('socialLink2Url', UrlType::class, ['label' => 'URL réseau social 2', 'required' => false])
            ->add('primaryColor', ColorType::class, [
                'label' => 'Couleur principale',
                'required' => false,
            ])
            ->add('secondaryColor', ColorType::class, [
                'label' => 'Couleur secondaire',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => City::class,
            'show_slug' => true,
        ]);
        $resolver->setAllowedTypes('show_slug', 'bool');
    }
}
