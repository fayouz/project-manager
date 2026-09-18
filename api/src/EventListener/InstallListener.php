<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Repository\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class InstallListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 101]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        try {
            $isInstalled = $this->userRepository->hasAnyUser();
        } catch (\Throwable) {
            $isInstalled = false;
        }

        // Routes à exclure (profiler, wdt, setup lui-même, status api)
        if (str_starts_with($pathInfo, '/_profiler') || 
            str_starts_with($pathInfo, '/_wdt') || 
            str_starts_with($pathInfo, '/_error') ||
            $pathInfo === '/setup' ||
            $pathInfo === '/api/install-status' ||
            $pathInfo === '/api/setup'
        ) {
            if ($isInstalled && $pathInfo === '/setup') {
                $event->setResponse(new RedirectResponse('/'));
            }
            return;
        }

        if (!$isInstalled) {
            $event->setResponse(new RedirectResponse('/setup'));
        }
    }
}
