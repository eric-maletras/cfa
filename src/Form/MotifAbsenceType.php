<?php

namespace App\Form;

use App\Entity\MotifAbsence;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MotifAbsenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextType::class, [
                'label' => 'Libellé',
                'attr' => [
                    'placeholder' => 'Ex: Maladie, RDV médical...',
                    'class' => 'form-control',
                ],
                'help' => 'Nom affiché dans les listes déroulantes.',
            ])
            ->add('code', TextType::class, [
                'label' => 'Code',
                'attr' => [
                    'placeholder' => 'Ex: MALADIE, RDV_MEDICAL...',
                    'class' => 'form-control',
                    'style' => 'text-transform: uppercase;',
                ],
                'help' => 'Code unique en majuscules (utilisé pour l\'export).',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description optionnelle du motif...',
                    'class' => 'form-control',
                    'rows' => 3,
                ],
            ])
            ->add('justificatifObligatoire', CheckboxType::class, [
                'label' => 'Justificatif obligatoire',
                'required' => false,
                'help' => 'Si coché, un document justificatif sera demandé (certificat médical, etc.).',
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'label_attr' => [
                    'class' => 'form-check-label',
                ],
            ])
            ->add('couleur', ChoiceType::class, [
                'label' => 'Couleur',
                'required' => false,
                'choices' => [
                    'Par défaut' => null,
                    'Vert' => 'success',
                    'Bleu' => 'info',
                    'Jaune' => 'warning',
                    'Rouge' => 'danger',
                    'Gris' => 'secondary',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('icone', ChoiceType::class, [
                'label' => 'Icône',
                'required' => false,
                'choices' => [
                    'Aucune' => null,
                    '🏥 Médical' => '🏥',
                    '🤒 Maladie' => '🤒',
                    '🚗 Transport' => '🚗',
                    '👨‍👩‍👧 Famille' => '👨‍👩‍👧',
                    '📋 Administratif' => '📋',
                    '⚖️ Juridique' => '⚖️',
                    '🎓 Formation' => '🎓',
                    '💼 Entreprise' => '💼',
                    '❓ Autre' => '❓',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('ordre', IntegerType::class, [
                'label' => 'Ordre d\'affichage',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                ],
                'help' => 'Les motifs sont triés par ordre croissant.',
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
                'help' => 'Décocher pour masquer ce motif sans le supprimer.',
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'label_attr' => [
                    'class' => 'form-check-label',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MotifAbsence::class,
        ]);
    }
}
