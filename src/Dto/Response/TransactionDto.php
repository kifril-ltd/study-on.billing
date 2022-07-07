<?php

namespace App\Dto\Response;

use JMS\Serializer\Annotation as Serialization;

class TransactionDto
{
    #[Serialization\Type("integer")]
    public int $id;

    #[Serialization\Type("DateTimeImmutable")]
    public \DateTimeImmutable $createdAt;

    #[Serialization\Type("string")]
    public string $type;

    #[Serialization\Type("string"), Serialization\SkipWhenEmpty]
    public ?string $courseCode;

    #[Serialization\Type("float")]
    public float $amount;

    #[Serialization\Type("DateTimeImmutable"), Serialization\SkipWhenEmpty]
    public ?\DateTimeImmutable $expiresAt;
}