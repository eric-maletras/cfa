<?php

namespace App\Form;

use App\Entity\TypeCertification;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TypeCertificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code *',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: BTS, TP, CQP',
                    'maxlength' => 20,
                ],
            ])
            ->add('libelle', TextType::class, [
                'label' => 'Libellé complet *',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Brevet de technicien supérieur',
                ],
            ])
            ->add('libelleAbrege', TextType::class, [
                'label' => 'Libellé abrégé',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: BTS',
                ],
            ])
            ->add('certificateurType', ChoiceType::class, [
                'label' => 'Type de certificateur *',
                'choices' => [
                    'Ministère' => 'ministere',
                    'Branche professionnelle' => 'branche',
                    'Organisme privé' => 'organisme_prive',
                    'Consulaire (CCI, CMA)' => 'consulaire',
                ],
                'attr' => ['class' => 'form-control'],
                'placeholder' => '-- Sélectionner --',
            ])
            ->add('certificateurNom', TextType::class, [
                'label' => 'Nom du certificateur',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Ministère de l\'Enseignement supérieur',
                ],
            ])
            ->add('enregistrementRncp', ChoiceType::class, [
                'label' => 'Enregistrement RNCP *',
                'choices' => [
                    'De droit' => 'de_droit',
                    'Sur demande' => 'sur_demande',
                    'Optionnel' => 'optionnel',
                    'Non applicable' => 'non_applicable',
                ],
                'attr' => ['class' => 'form-control'],
                'placeholder' => '-- Sélectionner --',
            ])
            ->add('apprentissagePossible', CheckboxType::class, [
                'label' => 'Préparable en apprentissage',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('vaePossible', CheckboxType::class, [
                'label' => 'Accessible par VAE',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                ],
            ])
            ->add('ordreAffichage', IntegerType::class, [
                'label' => 'Ordre d\'affichage',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                ],
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TypeCertification::class,
        ]);
    }
}
