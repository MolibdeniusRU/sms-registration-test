<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\RateLimitExceededException;
use App\Repository\UserRepository;
use App\Request\SmsRegistrationRequest;
use App\Service\SmsCodeService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Throwable;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CacheInterface $cache,
    )
    {
    }

    #[Route(
        path: '/sms-registration',
        name: 'sms-registration',
        methods: ['POST'],
        format: 'json')]
    public function SmsRegistration(
        #[MapRequestPayload] SmsRegistrationRequest $registrationRequest,
        #[ValueResolver('client_ip')] string        $clientIp,
        RateLimiterFactory                          $smsRegistrationLimiter,
        SmsCodeService                              $smsCodeService,
    ): Response
    {
        try {
            if ($registrationRequest->getCode() === null) {
                $limit = $smsRegistrationLimiter->create($clientIp)->consume();

                $this->checkLimit($limit, $clientIp);

                return $this->json(
                    ['code' => $smsCodeService->getCode($registrationRequest->getPhone())],
                    Response::HTTP_OK
                );

            }

            if (!$smsCodeService->validateCode($registrationRequest->getPhone(), $registrationRequest->getCode())) {
                return $this->json(
                    ['message' => 'Неверный код'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            if (!$user = $this->userRepository->findOneBy(['phone' => $registrationRequest->getPhone()])) {
                $user = new User();
                $this->userRepository->save($user->setPhone($registrationRequest->getPhone()));

                return $this->json(
                    [
                        'message' => 'Вы успешно зарегистрировались',
                        'user_id' => $user->getId()
                    ],
                    Response::HTTP_CREATED
                );
            }

            return $this->json(
                [
                    'message' => 'Вы успешно авторизовались',
                    'user_id' => $user->getId()
                ],
                Response::HTTP_OK
            );

        } catch (Throwable $exception) {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

            if ($exception instanceof RateLimitExceededException) {
                $statusCode = Response::HTTP_BAD_REQUEST;
            }

            return $this->json([
                'message' => $exception->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function checkLimit(RateLimit $limit, $clientIp): void
    {
        if (!$limit->isAccepted()) {
            throw new RateLimitExceededException(sprintf(
                    'Превышен лимит из %s запросов, повторите попытку после %s',
                    $limit->getLimit(),
                    $this->cache->get($clientIp, function (ItemInterface $item) use ($limit): string {
                        $item->expiresAfter($limit->getRetryAfter()->getTimestamp() - time());

                        return $limit->getRetryAfter()->format("Y-m-d H:i:s");
                    })
                )
            );
        }
    }
}
