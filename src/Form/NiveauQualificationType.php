<?php

namespace App\Form;

use App\Entity\NiveauQualification;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NiveauQualificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', IntegerType::class, [
                'label' => 'Code niveau (1-8) *',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 8,
                    'placeholder' => 'Ex: 5',
                ],
            ])
            ->add('libelle', TextType::class, [
                'label' => 'Libellé *',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Niveau 5 - BTS/DUT',
                ],
            ])
            ->add('equivalentDiplome', TextType::class, [
                'label' => 'Équivalent diplôme *',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: BTS, DUT, DEUST',
                ],
            ])
            ->add('ancienNiveau', TextType::class, [
                'label' => 'Ancien niveau (nomenclature 1969)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: III',
                ],
            ])
            ->add('niveauCec', IntegerType::class, [
                'label' => 'Niveau CEC (européen)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 8,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
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
            'data_class' => NiveauQualification::class,
        ]);
    }
}
