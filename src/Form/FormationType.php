<?php

namespace App\Form;

use App\Entity\Formation;
use App\Entity\NiveauQualification;
use App\Entity\TypeCertification;
use App\Entity\CodeNSF;
use App\Entity\CodeROME;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Informations générales
            ->add('intitule', TextType::class, [
                'label' => 'Intitulé officiel *',
                'attr' => [
                    'placeholder' => 'Ex: BTS Services informatiques aux organisations',
                    'class' => 'form-control',
                ],
            ])
            ->add('intituleCourt', TextType::class, [
                'label' => 'Intitulé court',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: BTS SIO',
                    'class' => 'form-control',
                ],
            ])
            ->add('codeRncp', TextType::class, [
                'label' => 'Code RNCP',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: RNCP35340',
                    'class' => 'form-control',
                ],
            ])
            
            // Relations avec tables de référence
            ->add('niveauQualification', EntityType::class, [
                'class' => NiveauQualification::class,
                'label' => 'Niveau de qualification *',
                'choice_label' => function(NiveauQualification $niveau) {
                    return 'Niveau ' . $niveau->getCode() . ' - ' . $niveau->getEquivalentDiplome();
                },
                'placeholder' => '-- Sélectionner --',
                'attr' => ['class' => 'form-control'],
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('n')
                        ->where('n.actif = true')
                        ->orderBy('n.code', 'ASC');
                },
            ])
            ->add('typeCertification', EntityType::class, [
                'class' => TypeCertification::class,
                'label' => 'Type de certification *',
                'choice_label' => function(TypeCertification $type) {
                    return $type->getLibelleAbrege() 
                        ? $type->getLibelleAbrege() . ' - ' . $type->getLibelle()
                        : $type->getLibelle();
                },
                'placeholder' => '-- Sélectionner --',
                'attr' => ['class' => 'form-control'],
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('t')
                        ->where('t.actif = true')
                        ->orderBy('t.ordreAffichage', 'ASC')
                        ->addOrderBy('t.libelle', 'ASC');
                },
            ])
            ->add('codesNsf', EntityType::class, [
                'class' => CodeNSF::class,
                'label' => 'Codes NSF',
                'choice_label' => function(CodeNSF $nsf) {
                    return $nsf->getCode() . ' - ' . $nsf->getLibelle();
                },
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control select2',
                    'data-placeholder' => 'Sélectionner les codes NSF...',
                ],
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('n')
                        ->where('n.actif = true')
                        ->andWhere('n.niveau = 3') // Niveau groupe
                        ->orderBy('n.code', 'ASC');
                },
            ])
            ->add('codesRome', EntityType::class, [
                'class' => CodeROME::class,
                'label' => 'Codes ROME (métiers visés)',
                'choice_label' => function(CodeROME $rome) {
                    return $rome->getCode() . ' - ' . $rome->getLibelle();
                },
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control select2',
                    'data-placeholder' => 'Sélectionner les codes ROME...',
                ],
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('r')
                        ->where('r.actif = true')
                        ->orderBy('r.code', 'ASC');
                },
            ])
            
            // Durée
            ->add('dureeHeures', IntegerType::class, [
                'label' => 'Durée en heures',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: 1350',
                    'class' => 'form-control',
                    'min' => 0,
                ],
            ])
            ->add('dureeMois', IntegerType::class, [
                'label' => 'Durée en mois',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: 24',
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 60,
                ],
            ])
            
            // Dates RNCP
            ->add('dateEnregistrement', DateType::class, [
                'label' => 'Date d\'enregistrement RNCP',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateEcheance', DateType::class, [
                'label' => 'Date d\'échéance RNCP',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            
            // Description
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'class' => 'form-control',
                    'placeholder' => 'Description de la formation...',
                ],
            ])
            ->add('objectifs', TextareaType::class, [
                'label' => 'Objectifs pédagogiques',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'class' => 'form-control',
                ],
            ])
            ->add('prerequis', TextareaType::class, [
                'label' => 'Prérequis',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'form-control',
                ],
            ])
            
            // Statut
            ->add('actif', CheckboxType::class, [
                'label' => 'Formation active',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formation::class,
        ]);
    }
}
