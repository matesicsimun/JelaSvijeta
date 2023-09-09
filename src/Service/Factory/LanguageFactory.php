<?php

namespace App\Service\Factory;

use App\Entity\Language;
use App\Interface\service\LanguageFactoryInterface;

class LanguageFactory implements LanguageFactoryInterface
{

    public function create(string $code, string $shortCode): Language
    {
        $language = new Language();
        $language->setCode($code);
        $language->setShortCode($shortCode);

        return $language;
    }
}