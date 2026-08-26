<?php

namespace GlpiPlugin\Usernotifications\Controller;

use Glpi\Controller\AbstractController;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use GlpiPlugin\Usernotifications\Manager;
use Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class MarkReadController extends AbstractController
{
    #[Route('/notifications/mark-read', name: 'usernotifications_mark_read', methods: ['GET', 'POST'])]
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    public function __invoke(Request $request): Response
    {
        // GET is declared only for GLPI 11.0.1-11.0.6 router compatibility.
        if (!$request->isMethod('POST')) {
            throw new MethodNotAllowedHttpException(['POST']);
        }

        Session::checkLoginUser();
        $submittedToken = (string) $request->request->get('plugin_usernotifications_mark_token', '');
        $sessionToken = (string) ($_SESSION['plugin_usernotifications_mark_token'] ?? '');
        if ($submittedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
            throw new AccessDeniedHttpException();
        }

        $userId = (int) Session::getLoginUserID();
        $notificationId = $request->request->getInt('id', 0);
        if ($notificationId > 0) {
            Manager::markAsRead($userId, $notificationId);
        } else {
            Manager::markAllAsRead($userId);
        }

        $nextToken = bin2hex(random_bytes(32));
        $_SESSION['plugin_usernotifications_mark_token'] = $nextToken;
        return new JsonResponse(['ok' => true, 'mark_token' => $nextToken, 'csrf_token' => Session::getNewCSRFToken()]);
    }
}