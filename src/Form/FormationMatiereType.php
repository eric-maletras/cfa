<?php

namespace App\Form;

use App\Entity\FormationMatiere;
use App\Entity\Matiere;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Formulaire de liaison Formation-Matière
 * Permet de définir le volume horaire et le coefficient
 */
class FormationMatiereType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        // En mode création, on affiche le sélecteur de matière
        if (!$isEdit) {
            $matieresDisponibles = $options['matieres_disponibles'] ?? [];
            
            $builder->add('matiere', EntityType::class, [
                'class' => Matiere::class,
                'choices' => $matieresDisponibles,
                'choice_label' => function (Matiere $matiere) {
                    return sprintf('%s - %s', $matiere->getCode(), $matiere->getLibelle());
                },
                'label' => 'Matière',
                'placeholder' => '-- Sélectionner une matière --',
                'attr' => [
                    'class' => 'form-select',
                ],
                'help' => 'Seules les matières actives non encore associées sont affichées',
            ]);
        }

        $builder
            ->add('volumeHeuresReferentiel', IntegerType::class, [
                'label' => 'Volume horaire (référentiel)',
                'attr' => [
                    'min' => 1,
                    'max' => 9999,
                    'placeholder' => 'Ex: 120',
                ],
                'help' => 'Nombre d\'heures prévues au référentiel sur la durée totale de la formation',
            ])
            ->add('coefficient', NumberType::class, [
                'label' => 'Coefficient',
                'required' => false,
                'scale' => 1,
                'attr' => [
                    'min' => 0,
                    'max' => 999.9,
                    'step' => 0.1,
                    'placeholder' => 'Ex: 2.5',
                ],
                'help' => 'Coefficient de la matière (optionnel)',
            ])
            ->add('ordre', IntegerType::class, [
                'label' => 'Ordre d\'affichage',
                'attr' => [
                    'min' => 0,
                    'placeholder' => 'Ex: 0',
                ],
                'help' => 'Ordre d\'affichage dans la liste (0 = premier)',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FormationMatiere::class,
            'is_edit' => false,
            'matieres_disponibles' => [],
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
        $resolver->setAllowedTypes('matieres_disponibles', 'array');
    }
}
