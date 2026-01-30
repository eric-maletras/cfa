<?php

namespace App\Form;

use App\Entity\CreneauRecurrent;
use App\Entity\Salle;
use App\Entity\Session;
use App\Entity\SessionMatiere;
use App\Entity\TypeSalle;
use App\Entity\User;
use App\Enum\JourSemaine;
use App\Enum\SemaineType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire de création/édition d'un créneau récurrent
 * 
 * Les champs sessionMatiere et formateurs sont dynamiques :
 * ils se rechargent via AJAX en fonction de la session sélectionnée.
 * 
 * Workflow :
 * 1. Sélection d'une session → déclenche rechargement AJAX
 * 2. Les matières et formateurs sont filtrés selon la session
 * 3. Les dates de période sont pré-remplies avec celles de la session
 */
class CreneauRecurrentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ========================================
            // SESSION (déclenche le rechargement dynamique)
            // ========================================
            ->add('session', EntityType::class, [
                'class' => Session::class,
                'label' => 'Session de formation',
                'placeholder' => '-- Sélectionner une session --',
                'choice_label' => function (Session $session): string {
                    return sprintf('%s - %s', $session->getCode(), $session->getLibelle());
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->where('s.actif = :actif')
                        ->setParameter('actif', true)
                        ->orderBy('s.dateDebut', 'DESC');
                },
                'attr' => [
                    'class' => 'form-control',
                    'data-dynamic-field' => 'session',
                ],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner une session.']),
                ],
            ])

            // ========================================
            // TYPE DE SEMAINE (Toutes / A / B)
            // ========================================
            ->add('semaineType', ChoiceType::class, [
                'label' => 'Type de semaine',
                'choices' => [
                    'Toutes les semaines' => null,
                    'Semaine A uniquement' => SemaineType::A,
                    'Semaine B uniquement' => SemaineType::B,
                ],
                'placeholder' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'Laissez sur "Toutes" si pas d\'alternance A/B.',
            ])

            // ========================================
            // JOUR DE LA SEMAINE
            // ========================================
            ->add('jourSemaine', EnumType::class, [
                'class' => JourSemaine::class,
                'label' => 'Jour',
                'placeholder' => '-- Sélectionner un jour --',
                'choice_label' => fn(JourSemaine $jour): string => $jour->getLibelle(),
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner un jour.']),
                ],
            ])

            // ========================================
            // HORAIRES
            // ========================================
            ->add('heureDebut', TimeType::class, [
                'label' => 'Heure de début',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'min' => '07:00',
                    'max' => '21:00',
                    'step' => '900', // 15 minutes
                ],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez indiquer l\'heure de début.']),
                ],
            ])

            ->add('heureFin', TimeType::class, [
                'label' => 'Heure de fin',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'min' => '07:00',
                    'max' => '22:00',
                    'step' => '900', // 15 minutes
                ],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez indiquer l\'heure de fin.']),
                ],
            ])

            // ========================================
            // PÉRIODE DE VALIDITÉ
            // ========================================
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez indiquer la date de début.']),
                ],
            ])

            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez indiquer la date de fin.']),
                ],
            ])

            // ========================================
            // SALLE (groupée par type - TypeSalle est une entité)
            // ========================================
            ->add('salle', EntityType::class, [
                'class' => Salle::class,
                'label' => 'Salle',
                'placeholder' => '-- Sélectionner une salle --',
                'choice_label' => function (Salle $salle): string {
                    $capacite = $salle->getCapacite() 
                        ? sprintf(' (%d places)', $salle->getCapacite()) 
                        : '';
                    return sprintf('%s - %s%s', $salle->getCode(), $salle->getLibelle(), $capacite);
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->where('s.actif = :actif')
                        ->setParameter('actif', true)
                        ->orderBy('s.type', 'ASC')
                        ->addOrderBy('s.code', 'ASC');
                },
                'group_by' => function (Salle $salle): string {
                    $type = $salle->getType();
                    if ($type instanceof TypeSalle) {
                        return $type->getLibelle();
                    }
                    // Si c'est un enum PHP
                    if (is_object($type) && method_exists($type, 'getLibelle')) {
                        return $type->getLibelle();
                    }
                    return $type?->value ?? 'Autre';
                },
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner une salle.']),
                ],
            ])

            // ========================================
            // OPTIONS
            // ========================================
            ->add('actif', CheckboxType::class, [
                'label' => 'Créneau actif',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'label_attr' => [
                    'class' => 'form-check-label',
                ],
                'help' => 'Un créneau inactif ne génère pas de nouvelles séances.',
            ])

            // ========================================
            // COMMENTAIRE
            // ========================================
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Notes, informations particulières...',
                ],
            ]);

        // ========================================
        // CHAMPS DYNAMIQUES (dépendent de la session)
        // ========================================
        
        /**
         * Fonction modificateur : ajoute/met à jour les champs
         * qui dépendent de la session sélectionnée
         */
        $formModifier = function (FormInterface $form, ?Session $session = null): void {
            // -----------------------------------------
            // MATIÈRE (SessionMatiere de la session)
            // -----------------------------------------
            $matieres = [];
            $matieresDisabled = true;
            $matieresPlaceholder = '-- Sélectionnez d\'abord une session --';

            if ($session !== null) {
                // Récupérer les SessionMatiere actives de la session
                $matieres = $session->getSessionMatieres()->filter(
                    fn(SessionMatiere $sm) => $sm->isActif()
                )->toArray();
                $matieresDisabled = false;
                $matieresPlaceholder = '-- Sélectionner une matière --';
            }

            $form->add('sessionMatiere', EntityType::class, [
                'class' => SessionMatiere::class,
                'label' => 'Matière',
                'placeholder' => $matieresPlaceholder,
                'choice_label' => function (SessionMatiere $sm): string {
                    $matiere = $sm->getMatiere();
                    if ($matiere === null) {
                        return '?';
                    }
                    return sprintf('%s - %s', $matiere->getCode(), $matiere->getLibelle());
                },
                'choices' => $matieres,
                'attr' => [
                    'class' => 'form-control',
                    'data-dynamic-target' => 'sessionMatiere',
                ],
                'disabled' => $matieresDisabled,
                'required' => true,
                'constraints' => $matieresDisabled ? [] : [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner une matière.']),
                ],
            ]);

            // -----------------------------------------
            // FORMATEURS (de la session)
            // -----------------------------------------
            $formateurs = [];
            $formateursDisabled = true;

            if ($session !== null) {
                $formateurs = $session->getFormateurs()->toArray();
                $formateursDisabled = false;
            }

            $form->add('formateurs', EntityType::class, [
                'class' => User::class,
                'label' => 'Formateur(s)',
                'multiple' => true,
                'expanded' => false, // Select multiple (pas checkboxes)
                'choice_label' => fn(User $user): string => $user->getNomComplet(),
                'choices' => $formateurs,
                'attr' => [
                    'class' => 'form-control',
                    'data-dynamic-target' => 'formateurs',
                    'size' => min(5, max(3, count($formateurs))), // Taille adaptative
                ],
                'disabled' => $formateursDisabled,
                'required' => true,
                'help' => 'Maintenez Ctrl (ou Cmd sur Mac) pour sélectionner plusieurs formateurs.',
                'constraints' => $formateursDisabled ? [] : [
                    new Assert\Count([
                        'min' => 1,
                        'minMessage' => 'Veuillez sélectionner au moins un formateur.',
                    ]),
                ],
            ]);
        };

        // -----------------------------------------
        // ÉVÉNEMENT PRE_SET_DATA
        // Appelé au chargement initial du formulaire
        // -----------------------------------------
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier): void {
                /** @var CreneauRecurrent|null $creneau */
                $creneau = $event->getData();
                $session = $creneau?->getSession();
                $formModifier($event->getForm(), $session);
            }
        );

        // -----------------------------------------
        // ÉVÉNEMENT POST_SUBMIT sur le champ session
        // Appelé quand la session change (soumission AJAX)
        // -----------------------------------------
        $builder->get('session')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier): void {
                /** @var Session|null $session */
                $session = $event->getForm()->getData();
                $parentForm = $event->getForm()->getParent();
                
                if ($parentForm !== null) {
                    $formModifier($parentForm, $session);
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreneauRecurrent::class,
            'attr' => [
                'novalidate' => 'novalidate', // Validation côté serveur uniquement
                'class' => 'creneau-form',
            ],
        ]);
    }
}
