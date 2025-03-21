<?php

namespace App\Request;

use App\Service\SmsCodeService;
use Symfony\Component\Validator\Constraints as Assert;

class SmsRegistrationRequest
{
    #[Assert\Type('string')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 12, max: 255)]
    private string $phone;

    #[Assert\Range(
        min: SmsCodeService::SMS_CODE_MIN,
        max: SmsCodeService::SMS_CODE_MAX
    )]
    private ?int $code = null;

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): SmsRegistrationRequest
    {
        $this->phone = $phone;
        return $this;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function setCode(?int $code): SmsRegistrationRequest
    {
        $this->code = $code;
        return $this;
    }
}