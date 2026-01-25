<?php

namespace App\Form;

use App\Entity\CodeNSF;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CodeNSFType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code NSF *',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 326 ou 326r',
                    'maxlength' => 10,
                ],
                'help' => 'Format : 1 chiffre (domaine), 2 chiffres (sous-domaine), 3 chiffres (groupe), 3 chiffres + lettre (spécialité)',
            ])
            ->add('libelle', TextType::class, [
                'label' => 'Libellé *',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Informatique, traitement de l\'information, réseaux',
                ],
            ])
            ->add('niveau', ChoiceType::class, [
                'label' => 'Niveau hiérarchique *',
                'choices' => [
                    '1 - Domaine' => 1,
                    '2 - Sous-domaine' => 2,
                    '3 - Groupe' => 3,
                    '4 - Spécialité fine' => 4,
                ],
                'attr' => ['class' => 'form-control'],
                'placeholder' => '-- Sélectionner --',
            ])
            ->add('typeDomaine', ChoiceType::class, [
                'label' => 'Type de domaine',
                'required' => false,
                'choices' => [
                    'Disciplinaire' => 'disciplinaire',
                    'Technico-production' => 'technico_prod',
                    'Technico-services' => 'technico_services',
                    'Développement personnel' => 'dev_personnel',
                ],
                'attr' => ['class' => 'form-control'],
                'placeholder' => '-- Sélectionner --',
            ])
            ->add('codeFonction', ChoiceType::class, [
                'label' => 'Code fonction (niveau 4)',
                'required' => false,
                'choices' => [
                    'm - Études et recherches' => 'm',
                    'n - Conception' => 'n',
                    'p - Organisation, gestion' => 'p',
                    'r - Contrôle, prévention, entretien' => 'r',
                    's - Production' => 's',
                    't - Installation, mise en place' => 't',
                    'u - Conduite, surveillance' => 'u',
                    'v - Autres domaines' => 'v',
                    'w - Plurifonctionnel' => 'w',
                ],
                'attr' => ['class' => 'form-control'],
                'placeholder' => '-- Aucune --',
            ])
            ->add('libelleFonction', TextType::class, [
                'label' => 'Libellé de la fonction',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('parent', EntityType::class, [
                'class' => CodeNSF::class,
                'label' => 'Code parent',
                'required' => false,
                'choice_label' => function(CodeNSF $nsf) {
                    return $nsf->getCode() . ' - ' . $nsf->getLibelle();
                },
                'placeholder' => '-- Aucun (niveau racine) --',
                'attr' => ['class' => 'form-control'],
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('n')
                        ->where('n.niveau < 4')
                        ->orderBy('n.code', 'ASC');
                },
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                ],
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CodeNSF::class,
        ]);
    }
}
