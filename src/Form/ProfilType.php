<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Votre pseudo',
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'first_options'  => ['label' => 'Nouveau mot de passe'],
                'second_options' => ['label' => 'Confirmer le mot de passe'],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'constraints' => [
                    new Assert\Length(
                        min: 8,
                        minMessage: 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        max: 4096
                    ),
                    new Assert\Regex(
                        pattern: '/[A-Z]/',
                        message: 'Votre mot de passe doit contenir au moins une lettre majuscule'
                    ),
                    new Assert\Regex(
                        pattern: '/[a-z]/',
                        message: 'Votre mot de passe doit contenir au moins une lettre minuscule'
                    ),
                    new Assert\Regex(
                        pattern: '/\d/',
                        message: 'Votre mot de passe doit contenir au moins un chiffre'
                    ),
                    new Assert\Regex(
                        pattern: '/[\W_]/',
                        message: 'Votre mot de passe doit contenir au moins un caractère spécial'
                    ),
                ],
            ])
            ->add('bio', TextareaType::class, [
                'required' => false,
                'label' => 'Biographie',
                'attr' => [
                    'placeholder' => 'Parlez un peu de vous...',
                    'class' => 'bio-textarea',
                    'maxlength' => 1000
                ],
                'constraints' => [
                    new Length(
                        max: 1000,
                        maxMessage: 'La biographie ne peut pas dépasser {{ limit }} caractères.',
                        normalizer: function (?string $value) {
                            return $value === null ? null : str_replace(["\r\n", "\r"], "\n", $value);
                        }
                    )
                ]
            ])
            ->add('profilePicture', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '10M',
                        mimeTypes: ['image/jpeg', 'image/png'],
                        mimeTypesMessage: 'Veuillez uploader un fichier image valide (jpg ou png)'
                    )
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
