<?php

namespace App\Form;

use App\Entity\Devoir;
use App\Entity\Session;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de création/modification d'un devoir
 */
class DevoirType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du devoir',
                'attr' => [
                    'placeholder' => 'Ex: Contrôle sur les réseaux TCP/IP',
                    'class' => 'form-control',
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => array_flip(Devoir::TYPES),
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateDevoir', DateType::class, [
                'label' => 'Date du devoir',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateLimite', DateType::class, [
                'label' => 'Date limite de rendu',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('coefficient', NumberType::class, [
                'label' => 'Coefficient',
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0.1,
                    'max' => 10,
                    'step' => 0.1,
                ],
            ])
            ->add('bareme', NumberType::class, [
                'label' => 'Barème (note maximale)',
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'step' => 1,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description / Consignes',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Consignes, objectifs, compétences évaluées...',
                ],
            ])
            ->add('visible', CheckboxType::class, [
                'label' => 'Visible par les apprenants',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
            ->add('commentaireInterne', TextareaType::class, [
                'label' => 'Notes internes (non visibles)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                    'placeholder' => 'Notes personnelles sur ce devoir...',
                ],
            ]);
        
        // Ajout du sélecteur de session si option activée
        if ($options['show_session_selector']) {
            $builder->add('session', EntityType::class, [
                'class' => Session::class,
                'choice_label' => function (Session $session) {
                    return $session->getCode() . ' - ' . $session->getLibelle();
                },
                'label' => 'Session',
                'placeholder' => 'Choisir une session',
                'attr' => ['class' => 'form-control'],
                'query_builder' => function ($repository) use ($options) {
                    $qb = $repository->createQueryBuilder('s')
                        ->andWhere('s.actif = true')
                        ->orderBy('s.dateDebut', 'DESC');
                    
                    // Filtre par formateur si fourni
                    if ($options['formateur'] !== null) {
                        $qb->leftJoin('s.formateurs', 'f')
                           ->andWhere('f = :formateur OR s.responsable = :formateur')
                           ->setParameter('formateur', $options['formateur']);
                    }
                    
                    return $qb;
                },
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Devoir::class,
            'show_session_selector' => false,
            'formateur' => null,
        ]);
        
        $resolver->setAllowedTypes('show_session_selector', 'bool');
        $resolver->setAllowedTypes('formateur', ['null', 'App\Entity\User']);
    }
}
