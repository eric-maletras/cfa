<?php

namespace App\Form;

use App\Entity\Inscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $formationOptions = $options['formation_options'];
        
        $builder
            ->add('dateInscription', DateType::class, [
                'label' => 'Date d\'inscription',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => array_flip(Inscription::STATUTS),
                'attr' => ['class' => 'form-control'],
            ])
            ->add('numeroContrat', TextType::class, [
                'label' => 'N° de contrat',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Numéro du contrat d\'apprentissage',
                ],
            ])
            ->add('dateDebutEffective', DateType::class, [
                'label' => 'Date de début effective',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'help' => 'Si différente de la date de début de session',
            ])
            ->add('dateFinEffective', DateType::class, [
                'label' => 'Date de fin effective',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'help' => 'En cas d\'abandon ou fin anticipée',
            ])
            ->add('motif', TextareaType::class, [
                'label' => 'Motif',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Motif en cas de refus, annulation ou abandon',
                ],
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire interne',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Notes administratives',
                ],
            ])
        ;
        
        // Ajouter le champ option si la formation en propose
        if (!empty($formationOptions)) {
            $choices = array_combine($formationOptions, $formationOptions);
            $builder->add('option', ChoiceType::class, [
                'label' => 'Option / Spécialité',
                'choices' => $choices,
                'required' => false,
                'placeholder' => 'Sélectionner une option',
                'attr' => ['class' => 'form-control'],
            ]);
        } else {
            $builder->add('option', TextType::class, [
                'label' => 'Option / Spécialité',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: SISR, SLAM...',
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Inscription::class,
            'formation_options' => [],
        ]);
        
        $resolver->setAllowedTypes('formation_options', 'array');
    }
}
