<?php

namespace App\Dto\Response;

use JMS\Serializer\Annotation as Serialization;

class CourseDto
{
    #[Serialization\Type("string")]
    public string $code;

    #[Serialization\Type("string")]
    public string $title;

    #[Serialization\Type("string")]
    public string $type;

    #[Serialization\Type("float")]
    public float $price;
}