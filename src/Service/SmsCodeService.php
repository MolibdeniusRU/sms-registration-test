<?php

namespace App\Service;

use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

readonly class SmsCodeService
{
    public const int SMS_CODE_MIN = 1000;

    public const int SMS_CODE_MAX = 9999;

    public const int SMS_CODE_CACHE_TIME = 60;

    public function __construct(private CacheInterface $cache,)
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getCode(string $phone): int
    {
        return $this->cache->get($phone, function (ItemInterface $item): int {
            $item->expiresAfter(self::SMS_CODE_CACHE_TIME);

            return random_int(self::SMS_CODE_MIN, self::SMS_CODE_MAX);
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    public function validateCode(string $phone, int $code): bool
    {
        return $this->getCode($phone) === $code;
    }
}