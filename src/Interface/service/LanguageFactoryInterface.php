<?php

namespace App\Interface\service;

use App\Entity\Language;

interface LanguageFactoryInterface
{
    public function create(string $code, string $shortCode): Language;
}
