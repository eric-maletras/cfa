<?php

namespace App\Form;

use App\Entity\CodeROME;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CodeROMEType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code ROME *',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: M1805',
                    'maxlength' => 5,
                    'pattern' => '[A-N][0-9]{4}',
                ],
                'help' => 'Format : 1 lettre (A-N) + 4 chiffres',
            ])
            ->add('libelle', TextType::class, [
                'label' => 'Intitulé du métier *',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Études et développement informatique',
                ],
            ])
            ->add('domaineCode', ChoiceType::class, [
                'label' => 'Domaine professionnel *',
                'choices' => [
                    'A - Agriculture et pêche, espaces naturels et espaces verts, soins aux animaux' => 'A',
                    'B - Arts et façonnage d\'ouvrages d\'art' => 'B',
                    'C - Banque, assurance, immobilier' => 'C',
                    'D - Commerce, vente et grande distribution' => 'D',
                    'E - Communication, média et multimédia' => 'E',
                    'F - Construction, bâtiment et travaux publics' => 'F',
                    'G - Hôtellerie-restauration, tourisme, loisirs et animation' => 'G',
                    'H - Industrie' => 'H',
                    'I - Installation et maintenance' => 'I',
                    'J - Santé' => 'J',
                    'K - Services à la personne et à la collectivité' => 'K',
                    'L - Spectacle' => 'L',
                    'M - Support à l\'entreprise' => 'M',
                    'N - Transport et logistique' => 'N',
                ],
                'attr' => ['class' => 'form-control'],
                'placeholder' => '-- Sélectionner --',
            ])
            ->add('domaineLibelle', TextType::class, [
                'label' => 'Libellé du domaine',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Support à l\'entreprise',
                ],
            ])
            ->add('sousDomaineCode', TextType::class, [
                'label' => 'Code sous-domaine',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 18',
                    'maxlength' => 2,
                ],
            ])
            ->add('sousDomaineLibelle', TextType::class, [
                'label' => 'Libellé sous-domaine',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Systèmes d\'information et de télécommunication',
                ],
            ])
            ->add('definition', TextareaType::class, [
                'label' => 'Définition du métier',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                ],
            ])
            ->add('conditionsAcces', TextareaType::class, [
                'label' => 'Conditions d\'accès',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                ],
            ])
            ->add('versionRome', ChoiceType::class, [
                'label' => 'Version ROME',
                'choices' => [
                    'ROME 4.0 (2023)' => '4.0',
                    'ROME 3.0' => '3.0',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateMaj', DateType::class, [
                'label' => 'Date de mise à jour',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
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
            'data_class' => CodeROME::class,
        ]);
    }
}
