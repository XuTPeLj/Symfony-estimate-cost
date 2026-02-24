<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Service;
use App\Form\OrderType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;

class OrderController extends AbstractController
{
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $services = $entityManager->getRepository(Service::class)->findAll();

        $userOrders = $entityManager->getRepository(Order::class)->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        $order = new Order();
        $order->setUser($this->getUser());
        $order->setEmail($this->getUser()->getEmail()); // Устанавливаем email пользователя

        $form = $this->createForm(OrderType::class, $order, [
            'services' => $services
        ]);

        $errors = [];

        try {
            $form->handleRequest($request);
        } catch (\Throwable $exception) {
            $errors = [(object)['message' => $exception->getMessage()]];
        }

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $order->setCreatedAt(new \DateTime());

                $entityManager->persist($order);
                $entityManager->flush();

                $this->addFlash('success', 'Заказ успешно создан!');

                return $this->redirectToRoute('app_order');
            } else {
                $errors = $form->getErrors(true);
            }
        }

        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'services' => $services,
            'userOrders' => $userOrders,
            'errors' => $errors
        ])->setStatusCode(
            $form->isSubmitted() && !$form->isValid()
                ? Response::HTTP_BAD_REQUEST
                : Response::HTTP_OK
        );
    }
}
