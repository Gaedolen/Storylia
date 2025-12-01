<?php

namespace App\Form;

use App\Entity\Club;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ClubType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du club',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('preferences', ChoiceType::class, [
                'label' => 'Vos préférences de lecture',
                'choices' => [
                    'Fiction' => [
                        'Science-Fiction' => 'scifi',
                        'Fantastique' => 'fantastique',
                        'Fantasy' => 'fantasy',
                        'Dystopie' => 'dystopie',
                        'Steampunk' => 'steampunk',
                    ],
                    'Policier / Suspense' => [
                        'Policier' => 'policier',
                        'Thriller' => 'thriller',
                        'Espionnage' => 'espionnage',
                        'Horreur' => 'horreur',
                    ],
                    'Romance & Jeunesse' => [
                        'Aventure' => 'aventure',
                        'Young Adult' => 'young_adult',
                    ],
                    'Romance et roman adulte' => [
                        'Romance' => 'romance',
                        'Érotique' => 'erotique',
                        'Chick-lit' => 'chicklit',
                    ],
                    'Culture, Histoire & Documentaire' => [
                        'Essai' => 'essai',
                        'Biographie' => 'biographie',
                        'Philosophie' => 'philosophie',
                        'Historique' => 'historique',
                        'Science' => 'science',
                        'Sociologie' => 'sociologie',
                    ],
                    'Arts & Littérature' => [
                        'Poésie' => 'poesie',
                        'Théâtre' => 'theatre',
                    ],
                    'Mythes et Légendes' => [
                        'Contes et légendes' => 'conte_legend',
                        'Mythologie' => 'mythologie',
                    ],
                    'Graphique' => [
                        'Roman Graphique' => 'graphic_novel',
                        'Bande dessinée' => 'bd',
                        'Manga' => 'manga',
                    ],
                ],
                'expanded' => true,
                'multiple' => true,
                'required' => false,
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo du club (jpg, png)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Merci de télécharger une image JPEG ou PNG',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Club::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'club_item',
        ]);
    }
}