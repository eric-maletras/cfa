<?php

namespace App\Form;

use App\Entity\Matiere;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de création/modification d'une matière
 */
class MatiereType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code',
                'attr' => [
                    'placeholder' => 'Ex: SLAM, SISR, MATH',
                    'maxlength' => 20,
                    'class' => 'text-uppercase',
                    'pattern' => '[A-Za-z0-9\-]+',
                    'title' => 'Lettres majuscules, chiffres et tirets uniquement',
                ],
                'help' => 'Code unique de la matière (lettres majuscules, chiffres, tirets)',
            ])
            ->add('libelle', TextType::class, [
                'label' => 'Libellé',
                'attr' => [
                    'placeholder' => 'Ex: Mathématiques pour l\'informatique',
                    'maxlength' => 255,
                ],
                'help' => 'Nom complet de la matière',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Description optionnelle de la matière...',
                ],
                'help' => 'Description détaillée (optionnel)',
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Matière active',
                'required' => false,
                'help' => 'Une matière inactive ne peut plus être associée à de nouvelles formations',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Matiere::class,
        ]);
    }
}
