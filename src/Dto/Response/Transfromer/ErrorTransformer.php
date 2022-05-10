<?php

namespace App\Dto\Response\Transfromer;

use Symfony\Component\Validator\ConstraintViolationList;

class ErrorTransformer
{
    public function transformErrorsToArray(ConstraintViolationList $errors)
    {
        $jsonErorrs = [];
        foreach ($errors as $error) {
            $jsonErorrs[$error->getPropertyPath()] = $error->getMessage();
        }
        return $jsonErorrs;
    }
}