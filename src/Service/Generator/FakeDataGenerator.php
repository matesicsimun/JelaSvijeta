<?php

namespace App\Service\Generator;

use App\Interface\service\FakeDataGeneratorInterface;
use Faker\Factory;
use Faker\Generator;

class FakeDataGenerator implements FakeDataGeneratorInterface
{
    private Generator $faker;
    private array $languageFakers;

    public function __construct(array $languages = [])
    {
        $this->faker = Factory::create();
        $this->faker->seed(1000);
        $this->languageFakers = $this->generateLanguageFakers($languages);
    }

    public function generateDateTimes(int $numberOfDateTimes): array
    {
        $dateTimes = [];
        for ($i = 0; $i < $numberOfDateTimes; $i++) {
            $dateTimes[] = $this->faker->dateTime();
        }
        return $dateTimes;
    }

    public function generateUniqueWords(int $numberOfWords): array
    {
        $uniqueCodes = [];
        for($i = 0; $i < $numberOfWords; $i++) {
            $word = $this->faker->unique()->word();

            $uniqueCodes[] = $word;
        }

        return $uniqueCodes;
    }

    public function generateUniqueSlug(): string
    {
        return $this->faker->unique()->slug();
    }

    public function generateUniqueCity(): string
    {
        return $this->faker->unique()->city();
    }

    public function generateNameInLanguage(string $lang): string
    {
        $langFaker = $this->languageFakers[$lang];
        return $langFaker->name();
    }

    private function generateLanguageFakers(array $languages): array
    {
        $languageFakers = [];
        foreach ($languages as $lang) {
            $langFaker = Factory::create($lang);
            $langFaker->seed(100);
            $languageFakers[$lang] = $langFaker;
        }

        return $languageFakers;
    }

    public function generateUniqueWord(): string
    {
        return $this->faker->unique()->word();
    }
}
