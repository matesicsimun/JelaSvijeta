<?php

namespace App\Interface\service;

interface FakeDataGeneratorInterface
{

    public function generateDateTimes(int $numberOfDateTimes): array;

    public function generateUniqueWords(int $numberOfWords): array;

    public function generateUniqueWord(): string;

    public function generateUniqueSlug(): string;

    public function generateUniqueCity(): string;

    public function generateNameInLanguage(string $lang): string;
}