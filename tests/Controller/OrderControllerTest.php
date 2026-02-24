<?php
// tests/Controller/OrderControllerTest.php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Service;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class OrderControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private ?EntityManagerInterface $entityManager = null;
    private ?UserPasswordHasherInterface $passwordHasher = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $this->entityManager->createQuery('DELETE FROM App\Entity\Order')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    public function testAccessWithoutAuthentication(): void
    {
        $this->client->request('GET', '/order');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testAuthenticatedUserCanAccessOrderForm(): void
    {
        $user = $this->createTestUser();

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/order');

        $this->assertResponseIsSuccessful();

        // Проверяем наличие всех полей
        $this->assertSelectorExists('input[name="order[email]"]');
        $this->assertSelectorExists('select[name="order[service]"]');
        $this->assertSelectorExists('button[type="submit"]');

        // Проверяем наличие информации о пользователе
        $this->assertSelectorTextContains('.user-info', $user->getEmail());
    }

    public function testSubmitOrderWithInvalidData(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request('POST', '/order', [
            'order' => [
                'email' => '',
                'service' => ''
            ]
        ]);

        $this->assertResponseStatusCodeSame(400);

        $this->assertSelectorExists('.error');

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Пожалуйста, укажите email', $content);
        $this->assertStringContainsString('Пожалуйста, выберите услугу', $content);
    }

    // Отправляем форму с валидными данными
    public function testSubmitOrderWithValidData(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $service = $this->entityManager->getRepository(Service::class)->findOneBy([]);

        $initialCount = $this->entityManager->getRepository(Order::class)->count([]);

        $crawler = $this->client->request('GET', '/order');
        $token = $crawler->filter('input[name="order[_token]"]')->attr('value');

        $this->client->request('POST', '/order', [
            'order' => [
                'email' => 'test@example.com',
                'service' => $service->getId(),
                '_token' => $token
            ]
        ]);

        $this->assertResponseRedirects('/order');

        $this->client->followRedirect();

        // Проверяем наличие flash-сообщения
        $this->assertSelectorExists('.alert-success');

        // Проверяем, что заказ создан в БД
        $finalCount = $this->entityManager->getRepository(Order::class)->count([]);
        $this->assertEquals($initialCount + 1, $finalCount);

        // Проверяем данные последнего заказа
        $lastOrder = $this->entityManager->getRepository(Order::class)->findOneBy([], ['id' => 'DESC']);
        $this->assertEquals('test@example.com', $lastOrder->getEmail());
        $this->assertEquals($service->getId(), $lastOrder->getService()->getId());
        $this->assertEquals($user->getId(), $lastOrder->getUser()->getId());
    }

    // Создаем тестового пользователя и логинимся
    public function testSubmitOrderWithInvalidEmail(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $service = $this->entityManager->getRepository(Service::class)->findOneBy([]);

        $this->client->request('POST', '/order', [
            'order' => [
                'email' => 'not-an-email',
                'service' => $service->getId()
            ]
        ]);

        $this->assertResponseStatusCodeSame(400);

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Пожалуйста, укажите корректный email', $content);
    }

    private function createTestUser(): User
    {
        $user = new User();
        $email = 'testuser_' . uniqid() . '@example.com';
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'test123'));
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function tearDown(): void
    {
        if ($this->entityManager) {
            $this->entityManager->createQuery('DELETE FROM App\Entity\Order')->execute();

            $this->entityManager->close();
            $this->entityManager = null;
        }

        parent::tearDown();
    }
}
