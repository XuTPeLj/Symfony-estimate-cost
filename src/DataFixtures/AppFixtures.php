<?php
// app/src/DataFixtures/AppFixtures.php

namespace App\DataFixtures;

use App\Entity\Service;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Создаем услуги
        $services = [
            ['Оценка стоимости автомобиля', 500, 'Профессиональная оценка рыночной стоимости автомобиля'],
            ['Оценка стоимости квартиры', 800, 'Оценка рыночной стоимости недвижимости'],
            ['Оценка стоимости бизнеса', 1500, 'Комплексная оценка бизнеса и активов'],
        ];

        foreach ($services as $serviceData) {
            $service = new Service();
            $service->setName($serviceData[0]);
            $service->setPrice($serviceData[1]);
            $service->setDescription($serviceData[2]);
            $manager->persist($service);
        }

        // Создаем тестового пользователя
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'admin123'));
        $manager->persist($user);

        $manager->flush();
    }
}
