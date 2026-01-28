<?php

namespace App\Form;

use App\Entity\CalendrierAnnee;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire pour l'entité CalendrierAnnee
 */
class CalendrierAnneeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code',
                'help' => 'Format : AAAA-AAAA (ex: 2025-2026)',
                'attr' => [
                    'placeholder' => '2025-2026',
                    'pattern' => '\d{4}-\d{4}',
                    'maxlength' => 9,
                ],
            ])
            ->add('libelle', TextType::class, [
                'label' => 'Libellé',
                'help' => 'Ex: Année scolaire 2025-2026',
                'attr' => [
                    'placeholder' => 'Année scolaire 2025-2026',
                    'maxlength' => 100,
                ],
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'help' => 'Généralement le 1er septembre',
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'help' => 'Généralement le 31 août de l\'année suivante',
            ])
            ->add('heureDebutDefaut', TimeType::class, [
                'label' => 'Heure de début par défaut',
                'widget' => 'single_text',
                'help' => 'Heure de début des journées de formation',
            ])
            ->add('heureFinDefaut', TimeType::class, [
                'label' => 'Heure de fin par défaut',
                'widget' => 'single_text',
                'help' => 'Heure de fin des journées de formation',
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Calendrier actif',
                'required' => false,
                'help' => 'Un seul calendrier devrait être actif à la fois',
            ]);

        // Auto-génération du libellé si vide lors de la soumission
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $calendrier = $event->getData();
            if ($calendrier instanceof CalendrierAnnee) {
                if (empty($calendrier->getLibelle()) && !empty($calendrier->getCode())) {
                    $calendrier->setLibelle($calendrier->genererLibelle());
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CalendrierAnnee::class,
        ]);
    }
}
