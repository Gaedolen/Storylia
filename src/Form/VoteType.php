<?php

namespace App\Form;

use App\Entity\Vote;
use App\Entity\BookProposal;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bookProposal', EntityType::class, [
                'class' => BookProposal::class,
                'choice_label' => function(BookProposal $proposal) {
                    return $proposal->getBook()->getTitle();
                },
                'placeholder' => 'Sélectionnez un livre',
                'expanded' => true,
                'multiple' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vote::class,
        ]);
    }
}
