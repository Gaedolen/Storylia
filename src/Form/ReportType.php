<?php

namespace App\Form;

use App\Entity\Report;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reported', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => 'pseudo',
                'placeholder' => 'Choisir un utilisateur…',
                'label' => 'Utilisateur signalé',
                'required' => true,
            ])
            ->add('reason', ChoiceType::class, [
                'choices' => [
                    'Harcèlement / comportement toxique' => 'harcelement',
                    'Usurpation d’identité' => 'usurpation',
                    'Spam' => 'spam',
                    'Contenu inapproprié' => 'contenu_inapproprie',
                    'Autre' => 'autre',
                ],
                'placeholder' => '-- Choisir un motif --',
                'label' => 'Motif',
                'required' => true,
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Report::class,
        ]);
    }
}
