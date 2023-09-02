<?php

namespace App\Converter;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class CodeNameConverter implements NameConverterInterface
{

    /**
     * @inheritDoc
     */
    public function normalize(string $propertyName): string
    {
        return str_replace('Code', '', $propertyName);
    }

    /**
     * @inheritDoc
     */
    public function denormalize(string $propertyName): string
    {
        // TODO: Implement denormalize() method.
    }
}