<?php

namespace App\Interface\service;

use App\Entity\Translation;

interface TranslationFactoryInterface
{
    public function create(string $languageCode, string $code, string $translation, string $shortCode): Translation;
}
