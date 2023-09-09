<?php

namespace App\Service\Factory;

use App\Entity\Translation;
use App\Interface\service\TranslationFactoryInterface;

class TranslationFactory implements TranslationFactoryInterface
{

    public function create(string $languageCode, string $code, string $translation, string $shortCode): Translation
    {
        $translation = new Translation();
        $translation->setLanguageCode($languageCode);
        $translation->setCode($code);
        $translation->setTranslation($translation);
        $translation->setShortCode($shortCode);

        return $translation;
    }
}