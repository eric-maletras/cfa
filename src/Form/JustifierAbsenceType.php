<?php

namespace App\Form;

use App\Entity\MotifAbsence;
use App\Repository\MotifAbsenceRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de justification d'une absence
 */
class JustifierAbsenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('motifAbsence', EntityType::class, [
                'class' => MotifAbsence::class,
                'choice_label' => function (MotifAbsence $motif) {
                    $label = $motif->getIcone() ? $motif->getIcone() . ' ' : '';
                    $label .= $motif->getLibelle();
                    if ($motif->isJustificatifObligatoire()) {
                        $label .= ' *';
                    }
                    return $label;
                },
                'query_builder' => function (MotifAbsenceRepository $repo) {
                    return $repo->createQueryBuilder('m')
                        ->andWhere('m.actif = true')
                        ->orderBy('m.ordre', 'ASC')
                        ->addOrderBy('m.libelle', 'ASC');
                },
                'label' => 'Motif',
                'placeholder' => '-- Sélectionner un motif --',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Précisions supplémentaires...',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Pas de data_class car on traite manuellement
        ]);
    }
}
