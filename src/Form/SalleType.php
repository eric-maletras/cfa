<?php

namespace App\Form;

use App\Entity\Salle;
use App\Entity\TypeSalle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire pour l'entité Salle
 * 
 * Gère la création et l'édition des salles avec validation
 * conditionnelle de la capacité selon le type de salle.
 */
class SalleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code',
                'attr' => [
                    'placeholder' => 'Ex: A101, LABO-IT-1',
                    'class' => 'form-control',
                    'maxlength' => 20,
                    'style' => 'text-transform: uppercase;',
                ],
                'help' => 'Lettres majuscules, chiffres et tirets uniquement.',
            ])
            ->add('libelle', TextType::class, [
                'label' => 'Libellé',
                'attr' => [
                    'placeholder' => 'Ex: Salle de cours A101',
                    'class' => 'form-control',
                    'maxlength' => 255,
                ],
            ])
            ->add('type', EnumType::class, [
                'class' => TypeSalle::class,
                'label' => 'Type de salle',
                'choice_label' => fn(TypeSalle $type) => $type->getLibelle(),
                'attr' => [
                    'class' => 'form-control',
                ],
                'placeholder' => '-- Sélectionner un type --',
                'help' => 'Le type "Virtuel" n\'a pas de capacité limitée.',
            ])
            ->add('capacite', IntegerType::class, [
                'label' => 'Capacité',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Nombre de places',
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 500,
                ],
                'help' => 'Obligatoire sauf pour les salles virtuelles.',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description détaillée de la salle (équipements, particularités...)',
                    'class' => 'form-control',
                    'rows' => 4,
                ],
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Salle active',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'label_attr' => [
                    'class' => 'form-check-label',
                ],
                'help' => 'Décocher pour désactiver temporairement la salle.',
            ]);

        // Listener pour gérer la capacité selon le type
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            
            // Si le type est virtuel, on force la capacité à null
            if (isset($data['type']) && $data['type'] === TypeSalle::VIRTUEL->value) {
                $data['capacite'] = null;
                $event->setData($data);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Salle::class,
            'attr' => [
                'novalidate' => 'novalidate', // Désactive validation HTML5 pour tester la validation Symfony
            ],
        ]);
    }
}
