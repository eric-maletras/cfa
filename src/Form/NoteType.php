<?php

namespace App\Form;

use App\Entity\Note;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire pour une note individuelle (utilisé dans la grille de saisie)
 */
class NoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('valeur', NumberType::class, [
                'label' => false,
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control form-control-sm note-input',
                    'min' => 0,
                    'step' => 0.5,
                    'placeholder' => '-',
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => false,
                'choices' => array_flip(Note::STATUTS),
                'attr' => [
                    'class' => 'form-control form-control-sm note-statut',
                ],
            ]);
        
        // Ajout du commentaire si demandé
        if ($options['show_commentaire']) {
            $builder->add('commentaire', TextareaType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-sm',
                    'rows' => 1,
                    'placeholder' => 'Commentaire...',
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Note::class,
            'show_commentaire' => false,
        ]);
        
        $resolver->setAllowedTypes('show_commentaire', 'bool');
    }
}
