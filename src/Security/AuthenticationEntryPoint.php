<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Twig\Environment;

class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    private $twig;

    public function __construct(Environment $twig = null)
    {
        $this->twig = $twig;
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        if ($request->isXmlHttpRequest() || str_starts_with($request->getPathInfo(), '/api/')) {
            return new JsonResponse([
                'error' => 'Unauthorized',
                'description' => 'Для доступа к этой странице необходима авторизация'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new Response(
            $this->twig->render('error.html.twig', [
                'title' => 'Доступ запрещен',
                'description' => 'Для доступа к этой странице необходимо авторизоваться.'
            ]),
            Response::HTTP_UNAUTHORIZED
        );
    }
}
