<?php

namespace App\Form;

use App\Entity\Book;
use App\Entity\Author;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;

class BookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre'
            ])
            ->add('voTitle', TextType::class, [
                'label' => 'Titre VO',
                'required' => false
            ])
            ->add('author', EntityType::class, [
                'class' => Author::class,
                'choice_label' => 'name',
                'label' => 'Auteur',
                'placeholder' => 'Sélectionnez un auteur'
            ])
            ->add('publicationDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de publication',
                'required' => false
            ])
            ->add('genres', TextType::class, [
                'label' => 'Genres (séparés par des virgules)',
                'required' => false,
            ])
            ->add('subjects', TextType::class, [
                'label' => 'Thèmes (séparés par des virgules)',
                'required' => false,
            ])
            ->add('summary', TextareaType::class, [
                'label' => 'Résumé',
                'required' => false
            ])
            ->add('isbn', TextType::class, [
                'label' => 'ISBN',
                'required' => false
            ])
            ->add('pages', IntegerType::class, [
                'label' => 'Nombre de pages',
                'required' => false
            ])
            ->add('publishers', TextType::class, [
                'label' => 'Éditeurs (séparés par des virgules)',
                'required' => false,
            ])
            ->add('format', TextType::class, [
                'label' => 'Format',
                'required' => false
            ])
            ->add('cover', FileType::class, [
                'label' => 'Couverture',
                'required' => false,
                'mapped' => false
            ]);


        /* ============================
           TRANSFORMERS JSON <-> STRING
           ============================ */

        foreach (['genres', 'subjects', 'publishers'] as $field) {
            $builder->get($field)->addModelTransformer(
                new CallbackTransformer(
                    fn (?array $data) => $data ? implode(', ', $data) : '',
                    fn (?string $string) => $string
                        ? array_map('trim', explode(',', $string))
                        : []
                )
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
        ]);
    }
}