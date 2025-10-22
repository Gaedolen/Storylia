<?php

namespace App\Form;

use App\Entity\Role;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('familyName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire.'])
                ],
            ])

            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(['message' => 'Le prénom est obligatoire.'])
                ],
            ])

            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
                'constraints' => [
                    new NotBlank(['message' => 'Le pseudo est obligatoire.'])
                ],
            ])

            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'L’email est obligatoire.']),
                    new Assert\Email(['message' => 'l\'adresse email "{{ value }}" n\'est pas valide.']),
                ],
            ])

            ->add('plainPassword', RepeatedType::class,[
                'type' => PasswordType::class,
                'mapped' => false, // plainPassword n'existe pas en BDD, on va devoir le hashé manuellement
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confimez le mot de passe'], 
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez rentrer un mot de passe.']),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                        'max' => 4096, // Limite de sécurité
                    ]),
                    new \Symfony\Component\Validator\Constraints\Regex([ // Inciter l'utilisateur à utiliser un mot de passe sécurisé
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
                        'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.'
                    ]),
                ],
            ])

            ->add('birthDate', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez renseigner votre date de naissance',
                    ]),
                    new Assert\LessThanOrEqual([
                        'value' => (new \DateTime())->modify('-16 years'),
                        'message' => 'Vous devez avoir au moins 16 ans pour vous inscrire.',
                    ]),
                ],
                'attr' => [
                    'max' => (new \DateTime())->modify('-16 years')->format('Y-m-d'),
                ],
            ])

            ->add('profilePicture', FileType::class, [
                'label' => 'Photo de profil (jpg, png)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier JPG ou PNG valide.',
                    ])
                ],
            ])

            ->add('agreeTerms', CheckBoxType::class, [
                'mapped' => false,
                'label_html' => true,
                'label' => 'J’accepte les <a href="'. $options['mentions_url'] .'">mentions légales</a> et la <a href="'. $options['confidentialite_url'] .'">politique de confidentialité</a>.',                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions pour continuer.'
                    ]),
                ],
            ])

            ->add('submit', SubmitType::class, [
                'label' => 'Créer un compte',
                'attr' => ['class' => 'btn-create-account'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
            'mentions_url' => null,
            'confidentialite_url' => null,
        ]);
    }
}
