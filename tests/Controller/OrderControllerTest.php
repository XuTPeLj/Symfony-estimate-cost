<?php
// app/tests/Controller/OrderControllerTest.php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Service;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class OrderControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        // Очищаем заказы перед каждым тестом
        $this->entityManager->createQuery('DELETE FROM App\Entity\Order')->execute();
    }

    public function testAccessWithoutAuthentication(): void
    {
        $this->client->request('GET', '/order');

        // Должно перенаправить на страницу логина
        $this->assertResponseRedirects('/login');
    }

    public function testAuthenticatedUserCanAccessOrderForm(): void
    {
        // Создаем тестового пользователя
        $user = $this->createTestUser();

        // Логинимся
        $this->client->loginUser($user);

        // Переходим на страницу заказа
        $crawler = $this->client->request('GET', '/order');

        // Проверяем, что страница доступна
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
        // Создаем тестового пользователя и логинимся
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        // Отправляем форму с пустыми данными
        $this->client->request('POST', '/order', [
            'order' => [
                'email' => '',
                'service' => ''
            ]
        ]);

        // Проверяем, что форма не прошла валидацию
        $this->assertResponseIsSuccessful(); // Страница не перенаправляет

        // Проверяем наличие ошибок
        $this->assertSelectorExists('.error');

        // Проверяем конкретные сообщения об ошибках
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Пожалуйста, укажите email', $content);
        $this->assertStringContainsString('Пожалуйста, выберите услугу', $content);
    }

    public function testSubmitOrderWithValidData(): void
    {
        // Создаем тестового пользователя и логинимся
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        // Получаем первую услугу из БД
        $service = $this->entityManager->getRepository(Service::class)->findOneBy([]);

        // Считаем количество заказов до отправки
        $initialCount = $this->entityManager->getRepository(Order::class)->count([]);

        // Отправляем форму с валидными данными
        $this->client->request('POST', '/order', [
            'order' => [
                'email' => 'test@example.com',
                'service' => $service->getId()
            ]
        ]);

        // Проверяем, что произошло перенаправление (успешное сохранение)
        $this->assertResponseRedirects('/order');

        // Следуем за редиректом
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

    public function testSubmitOrderWithInvalidEmail(): void
    {
        // Создаем тестового пользователя и логинимся
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        // Получаем первую услугу из БД
        $service = $this->entityManager->getRepository(Service::class)->findOneBy([]);

        // Отправляем форму с некорректным email
        $this->client->request('POST', '/order', [
            'order' => [
                'email' => 'not-an-email',
                'service' => $service->getId()
            ]
        ]);

        // Проверяем, что форма не прошла валидацию
        $this->assertResponseIsSuccessful();

        // Проверяем наличие ошибки email
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Пожалуйста, укажите корректный email', $content);
    }

    private function createTestUser(): User
    {
        $user = new User();
        $user->setEmail('testuser_' . uniqid() . '@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'test123'));
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Закрываем соединение с БД
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }
}
