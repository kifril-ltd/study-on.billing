<?php

namespace App\Dto\Response;

use JMS\Serializer\Annotation as Serialization;

class PaymentDto
{
    #[Serialization\Type("bool")]
    public bool $status;

    #[Serialization\Type("string")]
    public string $courseType;

    #[Serialization\Type("DateTimeImmutable")]
    public ?\DateTimeImmutable $expiresAt;
}