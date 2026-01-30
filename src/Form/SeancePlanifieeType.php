<?php

namespace App\Form;

use App\Entity\Salle;
use App\Entity\SeancePlanifiee;
use App\Entity\Session;
use App\Entity\SessionMatiere;
use App\Entity\User;
use App\Enum\StatutSeance;
use App\Repository\SalleRepository;
use App\Repository\SessionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire pour créer ou modifier une séance planifiée
 */
class SeancePlanifieeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('session', EntityType::class, [
                'class' => Session::class,
                'choice_label' => 'libelle',
                'query_builder' => function (SessionRepository $repo) {
                    return $repo->createQueryBuilder('s')
                        ->where('s.actif = true')
                        ->orderBy('s.libelle', 'ASC');
                },
                'label' => 'Session',
                'placeholder' => '-- Sélectionner une session --',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('heureDebut', TimeType::class, [
                'label' => 'Heure de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control', 'step' => 900], // 15 minutes
            ])
            ->add('heureFin', TimeType::class, [
                'label' => 'Heure de fin',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control', 'step' => 900],
            ])
            ->add('salle', EntityType::class, [
                'class' => Salle::class,
                'choice_label' => fn(Salle $s) => sprintf('%s (%s)', $s->getLibelle(), $s->getType()->getLibelle()),
                'query_builder' => function (SalleRepository $repo) {
                    return $repo->createQueryBuilder('s')
                        ->where('s.actif = true')
                        ->orderBy('s.libelle', 'ASC');
                },
                'label' => 'Salle',
                'placeholder' => '-- Sélectionner une salle --',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('statut', EnumType::class, [
                'class' => StatutSeance::class,
                'choice_label' => fn(StatutSeance $s) => $s->getIcone() . ' ' . $s->getLibelle(),
                'label' => 'Statut',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Notes, raison d\'annulation...',
                ],
            ])
        ;

        // Ajouter les champs dynamiques (matière et formateurs) en fonction de la session
        $formModifier = function (FormInterface $form, ?Session $session = null) {
            // Matières de la session
            $form->add('sessionMatiere', EntityType::class, [
                'class' => SessionMatiere::class,
                'choice_label' => fn(SessionMatiere $sm) => $sm->getMatiere()->getLibelle(),
                'query_builder' => function (EntityRepository $repo) use ($session) {
                    $qb = $repo->createQueryBuilder('sm')
                        ->join('sm.matiere', 'm')
                        ->where('sm.actif = true')
                        ->orderBy('m.libelle', 'ASC');
                    
                    if ($session) {
                        $qb->andWhere('sm.session = :session')
                           ->setParameter('session', $session);
                    } else {
                        $qb->andWhere('1 = 0'); // Aucune matière si pas de session
                    }
                    
                    return $qb;
                },
                'label' => 'Matière',
                'placeholder' => '-- Sélectionner une matière --',
                'attr' => ['class' => 'form-control'],
            ]);

            // Formateurs de la session
            $form->add('formateurs', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn(User $u) => $u->getNomComplet(),
                'query_builder' => function (EntityRepository $repo) use ($session) {
                    $qb = $repo->createQueryBuilder('u')
                        ->where('u.actif = true')
                        ->orderBy('u.nom', 'ASC')
                        ->addOrderBy('u.prenom', 'ASC');
                    
                    if ($session) {
                        $qb->innerJoin('u.rolesEntities', 'r')
                           ->andWhere('r.code = :roleCode')
                           ->setParameter('roleCode', 'ROLE_FORMATEUR');
                        
                        // Filtrer par formateurs de la session si disponible
                        if ($session->getFormateurs()->count() > 0) {
                            $qb->andWhere('u IN (:formateurs)')
                               ->setParameter('formateurs', $session->getFormateurs());
                        }
                    } else {
                        $qb->andWhere('1 = 0');
                    }
                    
                    return $qb;
                },
                'label' => 'Formateur(s)',
                'multiple' => true,
                'expanded' => false,
                'attr' => ['class' => 'form-control', 'size' => 5],
                'help' => 'Maintenez Ctrl pour sélectionner plusieurs formateurs',
            ]);
        };

        // Initialiser avec la session existante
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $seance = $event->getData();
                $formModifier($event->getForm(), $seance?->getSession());
            }
        );

        // Mettre à jour quand la session change
        $builder->get('session')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $session = $event->getForm()->getData();
                $formModifier($event->getForm()->getParent(), $session);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SeancePlanifiee::class,
        ]);
    }
}
