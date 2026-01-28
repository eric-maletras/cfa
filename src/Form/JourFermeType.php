<?php

namespace App\Form;

use App\Entity\JourFerme;
use App\Enum\TypeJourFerme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire pour l'entité JourFerme
 */
class JourFermeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'help' => 'Date du jour fermé',
            ])
            ->add('libelle', TextType::class, [
                'label' => 'Libellé',
                'help' => 'Ex: Toussaint, Fermeture Noël, Pont de l\'Ascension',
                'attr' => [
                    'placeholder' => 'Libellé du jour fermé',
                    'maxlength' => 100,
                ],
            ])
            ->add('type', EnumType::class, [
                'label' => 'Type',
                'class' => TypeJourFerme::class,
                'choice_label' => fn (TypeJourFerme $type) => $type->getIcone() . ' ' . $type->getLibelle(),
                'help' => 'Type de jour fermé',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JourFerme::class,
        ]);
    }
}
