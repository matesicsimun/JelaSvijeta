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
        $withoutCode = str_replace('Code', '', $propertyName);
        return str_replace('name', 'title', $withoutCode);
    }

    /**
     * @inheritDoc
     */
    public function denormalize(string $propertyName): string
    {
        // TODO: Implement denormalize() method.
    }
}