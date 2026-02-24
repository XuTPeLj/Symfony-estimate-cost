<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Service;
use App\Form\OrderType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    #[Route('/order', name: 'app_order')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Проверяем авторизацию через security-bundle
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $services = $entityManager->getRepository(Service::class)->findAll();

        $user = $this->getUser();
        $order = new Order($user);
        $order->setUser($user);
        $order->setEmail($user->getEmail());

        $form = $this->createForm(OrderType::class, $order, [
            'services' => $services
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setCreatedAt(new \DateTime());

            $entityManager->persist($order);
            $entityManager->flush();

            $this->addFlash('success', 'Заказ успешно создан!');

            return $this->redirectToRoute('app_order');
        }

        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'services' => $services,
            'errors' => $form->getErrors(true)
        ]);
    }

    #[Route('/api/service-price/{id}', name: 'api_service_price', methods: ['GET'])]
    public function getServicePrice(Service $service): Response
    {
        return $this->json([
            'price' => $service->getPrice()
        ]);
    }
}
