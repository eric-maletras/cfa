<?php

namespace App\Form;

use App\Entity\CreneauRecurrent;
use App\Entity\Session;
use App\Entity\SessionMatiere;
use App\Entity\Salle;
use App\Entity\User;
use App\Enum\JourSemaine;
use App\Enum\SemaineType;
use App\Repository\SessionMatiereRepository;
use App\Repository\SalleRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreneauRecurrentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Session $session */
        $session = $options['session'];

        $builder
            ->add('sessionMatiere', EntityType::class, [
                'class' => SessionMatiere::class,
                'label' => 'Matière',
                'placeholder' => '-- Sélectionner une matière --',
                'query_builder' => function (SessionMatiereRepository $repo) use ($session) {
                    return $repo->createQueryBuilder('sm')
                        ->leftJoin('sm.matiere', 'm')
                        ->addSelect('m')
                        ->where('sm.session = :session')
                        ->setParameter('session', $session)
                        ->orderBy('m.code', 'ASC');
                },
                'choice_label' => function (SessionMatiere $sm) {
                    $matiere = $sm->getMatiere();
                    return $matiere ? sprintf('%s - %s', $matiere->getCode(), $matiere->getLibelle()) : '?';
                },
                'attr' => ['class' => 'form-control'],
            ])
            ->add('jourSemaine', ChoiceType::class, [
                'label' => 'Jour de la semaine',
                'choices' => array_combine(
                    array_map(fn(JourSemaine $j) => $j->getLibelle(), JourSemaine::cases()),
                    JourSemaine::cases()
                ),
                'placeholder' => '-- Sélectionner un jour --',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('heureDebut', TimeType::class, [
                'label' => 'Heure de début',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'step' => 900, // 15 minutes
                ],
            ])
            ->add('heureFin', TimeType::class, [
                'label' => 'Heure de fin',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'step' => 900,
                ],
            ])
            ->add('salle', EntityType::class, [
                'class' => Salle::class,
                'label' => 'Salle',
                'placeholder' => '-- Sélectionner une salle --',
                'query_builder' => function (SalleRepository $repo) {
                    return $repo->createQueryBuilder('s')
                        ->where('s.actif = true')
                        ->orderBy('s.code', 'ASC');
                },
                'choice_label' => function (Salle $s) {
                    $label = $s->getCode();
                    if ($s->getCapacite()) {
                        $label .= sprintf(' (%d places)', $s->getCapacite());
                    }
                    if ($s->isVirtuel()) {
                        $label .= ' [Virtuel]';
                    }
                    return $label;
                },
                'attr' => ['class' => 'form-control'],
            ])
            ->add('formateurs', EntityType::class, [
                'class' => User::class,
                'label' => 'Formateur(s)',
                'multiple' => true,
                'expanded' => true, // Checkboxes
                'query_builder' => function (UserRepository $repo) use ($session) {
                    // Récupérer les IDs des formateurs de la session
                    $formateurIds = $session->getFormateurs()->map(fn($f) => $f->getId())->toArray();
                    
                    if (empty($formateurIds)) {
                        // Aucun formateur assigné à la session
                        return $repo->createQueryBuilder('u')
                            ->where('1 = 0'); // Retourne vide
                    }
                    
                    return $repo->createQueryBuilder('u')
                        ->where('u.id IN (:ids)')
                        ->setParameter('ids', $formateurIds)
                        ->orderBy('u.nom', 'ASC')
                        ->addOrderBy('u.prenom', 'ASC');
                },
                'choice_label' => fn(User $u) => $u->getNomComplet(),
                'attr' => ['class' => 'formateurs-checkboxes'],
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('semaineType', ChoiceType::class, [
                'label' => 'Type de semaine',
                'choices' => [
                    'Toutes les semaines' => null,
                    ...array_combine(
                        array_map(fn(SemaineType $s) => $s->getLibelle(), SemaineType::cases()),
                        SemaineType::cases()
                    ),
                ],
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Créneau actif',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Notes ou informations complémentaires...',
                ],
            ])
        ;

        // Pré-remplir les dates avec celles de la session si nouveau créneau
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($session) {
            $creneau = $event->getData();
            
            if ($creneau && $creneau->getId() === null) {
                // Nouveau créneau : pré-remplir avec les dates de la session
                if ($session->getDateDebut() && !$creneau->getDateDebut()) {
                    $creneau->setDateDebut($session->getDateDebut());
                }
                if ($session->getDateFin() && !$creneau->getDateFin()) {
                    $creneau->setDateFin($session->getDateFin());
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreneauRecurrent::class,
        ]);

        $resolver->setRequired('session');
        $resolver->setAllowedTypes('session', Session::class);
    }
}
