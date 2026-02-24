<?php
// app/src/Form/OrderType.php

namespace App\Form;

use App\Entity\Order;
use App\Entity\Service;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Электронная почта',
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, укажите email']),
                    new Email(['message' => 'Пожалуйста, укажите корректный email'])
                ]
            ])
            ->add('service', EntityType::class, [
                'class' => Service::class,
                'choices' => $options['services'],
                'empty_data' => '',
                'choice_label' => 'name',
                'label' => 'Выберите услугу',
                'placeholder' => '-- Выберите услугу --',
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, выберите услугу'])
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Создать заказ'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
            'services' => []
        ]);
    }
}
