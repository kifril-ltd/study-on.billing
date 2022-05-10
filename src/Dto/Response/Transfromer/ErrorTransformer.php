<?php

namespace App\Dto\Response\Transfromer;

use Symfony\Component\Validator\ConstraintViolationList;

class ErrorTransformer
{
    public function transformErrorsToArray(ConstraintViolationList $errors)
    {
        $jsonErrors = [];
        foreach ($errors as $error) {
            $jsonErrors[$error->getPropertyPath()] = $error->getMessage();
        }
        return $jsonErrors;
    }
}