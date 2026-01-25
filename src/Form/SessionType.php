<?php

namespace App\Form;

use App\Entity\Formation;
use App\Entity\Session;
use App\Entity\User;
use App\Repository\FormationRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de gestion des sessions
 */
class SessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // === SECTION IDENTIFICATION ===
            ->add('code', TextType::class, [
                'label' => 'Code *',
                'attr' => [
                    'placeholder' => 'Ex: BTSSIO-SISR-2024',
                    'class' => 'form-control text-uppercase',
                    'maxlength' => 50,
                ],
                'help' => 'Code unique (majuscules, chiffres, tirets). Sera généré automatiquement si vide.',
                'required' => false, // On le génère si vide
            ])
            
            ->add('libelle', TextType::class, [
                'label' => 'Libellé *',
                'attr' => [
                    'placeholder' => 'Ex: BTS SIO option SISR - Promotion 2024-2026',
                    'class' => 'form-control',
                    'maxlength' => 255,
                ],
            ])
            
            ->add('formation', EntityType::class, [
                'label' => 'Formation *',
                'class' => Formation::class,
                'choice_label' => function (Formation $formation): string {
                    $label = $formation->getIntituleCourt() ?? $formation->getIntitule();
                    if ($formation->getCodeRncp()) {
                        $label .= ' (' . $formation->getCodeRncp() . ')';
                    }
                    return $label;
                },
                'query_builder' => function (FormationRepository $repo) {
                    return $repo->createQueryBuilder('f')
                        ->andWhere('f.actif = :actif')
                        ->setParameter('actif', true)
                        ->orderBy('f.intitule', 'ASC');
                },
                'placeholder' => '-- Sélectionner une formation --',
                'attr' => ['class' => 'form-select'],
            ])
            
            // === SECTION DATES ===
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début *',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin *',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            
            ->add('dateDebutInscriptions', DateType::class, [
                'label' => 'Ouverture des inscriptions',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'help' => 'Laisser vide pour ne pas restreindre.',
            ])
            
            ->add('dateFinInscriptions', DateType::class, [
                'label' => 'Clôture des inscriptions',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            
            // === SECTION EFFECTIFS ===
            ->add('effectifMin', IntegerType::class, [
                'label' => 'Effectif minimum',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'placeholder' => 'Ex: 8',
                ],
                'help' => 'Nombre minimum d\'inscrits pour maintenir la session.',
            ])
            
            ->add('effectifMax', IntegerType::class, [
                'label' => 'Effectif maximum',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'placeholder' => 'Ex: 24',
                ],
                'help' => 'Capacité d\'accueil maximale.',
            ])
            
            // === SECTION ORGANISATION ===
            ->add('modalite', ChoiceType::class, [
                'label' => 'Modalité pédagogique',
                'choices' => array_flip(Session::MODALITES),
                'attr' => ['class' => 'form-select'],
            ])
            
            ->add('lieu', TextType::class, [
                'label' => 'Lieu de formation',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: Campus principal - Salle 201',
                    'class' => 'form-control',
                    'maxlength' => 255,
                ],
            ])
            
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => array_flip(Session::STATUTS),
                'attr' => ['class' => 'form-select'],
            ])
            
            // === SECTION ENCADREMENT ===
            ->add('responsable', EntityType::class, [
                'label' => 'Responsable pédagogique',
                'class' => User::class,
                'choice_label' => function (User $user): string {
                    return $user->getPrenom() . ' ' . $user->getNom();
                },
                'query_builder' => function (UserRepository $repo) {
                    // On filtre sur les utilisateurs ayant le rôle formateur
                    return $repo->createQueryBuilder('u')
                        ->leftJoin('u.rolesEntities', 'r')
                        ->andWhere('r.code IN (:codes)')
                        ->setParameter('codes', ['ROLE_FORMATEUR', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])
                        ->orderBy('u.nom', 'ASC')
                        ->addOrderBy('u.prenom', 'ASC');
                },
                'placeholder' => '-- Sélectionner un responsable --',
                'required' => false,
                'attr' => ['class' => 'form-select'],
            ])
            
            ->add('formateurs', EntityType::class, [
                'label' => 'Formateurs intervenants',
                'class' => User::class,
                'choice_label' => function (User $user): string {
                    return $user->getPrenom() . ' ' . $user->getNom();
                },
                'query_builder' => function (UserRepository $repo) {
                    return $repo->createQueryBuilder('u')
                        ->leftJoin('u.rolesEntities', 'r')
                        ->andWhere('r.code IN (:codes)')
                        ->setParameter('codes', ['ROLE_FORMATEUR', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])
                        ->orderBy('u.nom', 'ASC')
                        ->addOrderBy('u.prenom', 'ASC');
                },
                'multiple' => true,
                'expanded' => false, // Select multiple, pas checkboxes
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                    'size' => 5,
                ],
                'help' => 'Maintenir Ctrl pour sélectionner plusieurs formateurs.',
            ])
            
            // === SECTION AFFICHAGE ===
            ->add('couleur', ColorType::class, [
                'label' => 'Couleur d\'affichage',
                'required' => false,
                'attr' => ['class' => 'form-control form-control-color'],
                'help' => 'Couleur pour le calendrier et les plannings.',
            ])
            
            // === SECTION DIVERS ===
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire interne',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Notes internes...',
                ],
            ])
            
            ->add('actif', CheckboxType::class, [
                'label' => 'Session active',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
        ;

        // Génération automatique du code si vide
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $session = $event->getData();
            
            if ($session instanceof Session && empty($session->getCode())) {
                $generatedCode = $session->generateCode();
                if (!empty($generatedCode)) {
                    $session->setCode($generatedCode);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Session::class,
            'attr' => ['novalidate' => 'novalidate'], // Validation côté serveur
        ]);
    }
}
