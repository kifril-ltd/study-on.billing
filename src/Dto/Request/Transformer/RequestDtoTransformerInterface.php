<?php

namespace App\Dto\Request\Transformer;

interface RequestDtoTransformerInterface
{
    public function transformToObject($dtoObject);
}