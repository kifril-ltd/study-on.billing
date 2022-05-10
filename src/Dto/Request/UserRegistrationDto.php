<?php

namespace App\Dto\Request;

use JMS\Serializer\Annotation as Serialization;
use Symfony\Component\Validator\Constraints as Assert;

class UserRegistrationDto
{
    #[Serialization\Type("string")]
    #[Assert\Email(message: 'The email {{ value }} is not a valid email.')]
    #[Assert\NotBlank(message: 'The username field can\'t be blank.')]
    public string $username;

    #[Serialization\Type("string")]
    #[Assert\NotBlank(message: 'The password field can\'t be blank')]
    public string $password;
}
