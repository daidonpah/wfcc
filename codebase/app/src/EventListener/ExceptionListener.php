<?php

namespace App\EventListener;

use App\Exceptions\ExceptionWithStatusCode;
use App\Exceptions\FaultyEntityException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        switch (true) {
            case $exception instanceof FaultyEntityException:
                $event->setResponse(
                    new JsonResponse(
                        ['errors' => $exception->getErrors()],
                        JsonResponse::HTTP_BAD_REQUEST
                    )
                );
                break;
            case $exception instanceof ExceptionWithStatusCode:
                $event->setResponse(
                    new JsonResponse(
                        ['errors' => [['message' => $exception->getMessage()]]],
                        $exception->getCode()
                    )
                );
                break;
            case $exception instanceof NotFoundHttpException:
                $event->setResponse(
                    new JsonResponse(
                        ['errors' => [['message' => 'Route not found']]]
                    )
                );
                break;
            case $exception instanceof AccessDeniedHttpException:
                $event->setResponse(
                    new JsonResponse(
                        ['errors' => [['message' => 'Access Denied']]],
                        JsonResponse::HTTP_FORBIDDEN
                    )
                );
                break;
            default:
                if($_ENV['APP_ENV'] === 'prod' || $_ENV['APP_ENV'] === 'production') {
                    $event->setResponse(
                        new JsonResponse(
                            ['errors' => [['message' => $exception->getMessage()]]],
                            JsonResponse::HTTP_NOT_ACCEPTABLE
                        )
                    );
                } else {
                    $event->setResponse(
                        new JsonResponse(
                            ['errors' => [[
                                'message' => $exception->getMessage(),
                                'trace' => $exception->getTrace(),
                                'file' => $exception->getFile(),
                                'line' => $exception->getLine()
                            ]]]
                        )
                    );
                }
        }
    }
}