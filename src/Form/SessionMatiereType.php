<?php

namespace App\Form;

use App\Entity\Matiere;
use App\Entity\SessionMatiere;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de gestion d'une matière de session
 * 
 * En création : permet de sélectionner une matière et définir les volumes
 * En édition : permet de modifier les volumes planifiés/réalisés et le statut
 */
class SessionMatiereType extends AbstractType
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
                'help' => 'Matières actives non encore associées à cette session',
            ]);
        }

        $builder
            ->add('volumeHeuresReferentiel', IntegerType::class, [
                'label' => 'Volume horaire référentiel',
                'attr' => [
                    'min' => 1,
                    'max' => 9999,
                    'placeholder' => 'Ex: 120',
                ],
                'help' => 'Volume du référentiel (copié depuis la formation)',
                'disabled' => $isEdit, // Non modifiable en édition
            ])
            ->add('volumeHeuresPlanifie', IntegerType::class, [
                'label' => 'Volume horaire planifié',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'max' => 9999,
                    'placeholder' => 'Ex: 110',
                ],
                'help' => 'Volume prévu pour cette session (vide = référentiel)',
            ])
            ->add('volumeHeuresRealise', IntegerType::class, [
                'label' => 'Volume horaire réalisé',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'max' => 9999,
                    'placeholder' => 'Ex: 95',
                ],
                'help' => 'Volume effectivement réalisé',
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
                'help' => 'Coefficient de la matière',
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Matière active pour cette session',
                'required' => false,
                'help' => 'Désactiver pour exclure des calculs sans supprimer',
            ])
            ->add('ordre', IntegerType::class, [
                'label' => 'Ordre d\'affichage',
                'attr' => [
                    'min' => 0,
                    'placeholder' => 'Ex: 0',
                ],
                'help' => 'Position dans la liste (0 = premier)',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SessionMatiere::class,
            'is_edit' => false,
            'matieres_disponibles' => [],
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
        $resolver->setAllowedTypes('matieres_disponibles', 'array');
    }
}
