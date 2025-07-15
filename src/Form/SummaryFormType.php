<?php

namespace App\Form;

use App\Entity\ArticleSummary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SummaryFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('originalUrl', TextType::class, [
                'label' => 'Adres URL artykułu',
                'attr' => [
                    'placeholder' => 'Wklej tutaj adres URL artykułu',
                ],
            ])
            ->add('createdAt', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'd-none'], // Ukryj pole
                'label_attr' => ['class' => 'd-none'], // Ukryj etykietę
                'required' => false, // Nie wymagaj od użytkownika
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ArticleSummary::class,
        ]);
    }
}
