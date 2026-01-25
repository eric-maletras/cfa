<?php

namespace App\Form;

use App\Entity\Note;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire pour la saisie en grille des notes d'un devoir
 */
class SaisieNotesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('notes', CollectionType::class, [
                'entry_type' => NoteType::class,
                'entry_options' => [
                    'show_commentaire' => $options['show_commentaires'],
                ],
                'allow_add' => false,
                'allow_delete' => false,
                'by_reference' => false,
            ])
            ->add('publier', CheckboxType::class, [
                'label' => 'Publier les notes aux apprenants',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'show_commentaires' => true,
        ]);
        
        $resolver->setAllowedTypes('show_commentaires', 'bool');
    }
}
