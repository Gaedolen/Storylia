<?php

namespace App\Form;

use App\Entity\Utilisateur;
use App\Entity\Role;
use Dom\Text;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProfilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('familyName', TextType::class, [
                'label' => "Nom",
                'required' => true,
                'placeholder' => 'Votre nom',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
                'placeholder' => "Votre prénom",
            ])
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
                'required' => true,
                'placeholder' => 'Votre pseudo',
            ])
            ->add('presentation', TextType::class, [
                'label' => 'Présentez-vous',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Parlez un peu de vous...',
                    'rows' => 5,
                ],
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
                    'Policier / Suspence' => [
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
                        'Poésie' => "poesie",
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
            ->add('profilePicture', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'mawSize' => "2M",
                        'mimeType' => ['image/jpeg', 'image/png'],
                        'mimeTypeMessage' => 'Veuillez uploader un fichier image valide (jpg ou png)',
                    ])
                ],
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
