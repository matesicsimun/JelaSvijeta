<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\Dish;
use App\Entity\Ingredient;
use App\Entity\Language;
use App\Entity\Status;
use App\Entity\Tag;
use App\Entity\Translation;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;

class DatabaseOperator
{
    private EntityManagerInterface $em;
    private array $languages = ['hr_HR', 'en_EN', 'cs_CZ', 'de_DE', 'fr_FR'];

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function fillDatabase(): void
    {
        // TODO add check if DB is already filled

        $statuses = $this->createAndSaveStatuses();
        $this->createAndSaveLanguages();
        $languageFakers = $this->createAndSaveLanguageFakers();

        $codeFaker = Factory::create();
        $codeFaker->seed(100);

        $this->createAndSaveDishes($codeFaker, $statuses, $languageFakers);

        $this->em->flush();
    }

    public function cleanupDatabase(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Status')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Language')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Dish')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Category')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Ingredient')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Tag')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Translation')->execute();
    }

    private function createAndSaveStatuses(): array
    {
        $statusActive = new Status();
        $statusActive->setName('active');

        $statusModified = new Status();
        $statusModified->setName('modified');

        $statusDeleted = new Status();
        $statusDeleted->setName('deleted');

        $this->em->persist($statusModified);
        $this->em->persist($statusActive);
        $this->em->persist($statusDeleted);

        return [$statusActive, $statusDeleted, $statusModified];
    }

    private function createAndSaveLanguages(): void
    {
        foreach ($this->languages as $lang) {
            $language = new Language();
            $language->setCode($lang);
            $this->em->persist($language);
        }
    }

    private function createAndSaveLanguageFakers(): array
    {
        $languageFakers = [];
        foreach ($this->languages as $lang) {
            //create language faker
            $langFaker = Factory::create($lang);
            $langFaker->seed(100);
            $languageFakers[$lang] = $langFaker;
        }

        return $languageFakers;
    }

    // TODO - fix issue that no two dishes will have the same category, share any tag or ingredient
    // this is due to tags, categories and ingredients being generated along with dishes
    // one solution is to generate tags, categories and ingredients before generating dishes
    // and then randomly selecting between existing categories, tags and ingredients
    // which could be cached after creation
    private function createAndSaveDishes(Generator $codeFaker, array $statuses, array $languageFakers): void
    {
        $numOfDishes = 20;
        for ($i = 0; $i < $numOfDishes; $i++) {
            $dish = new Dish();

            $dish->setTitleCode($codeFaker->unique()->word());
            $dish->setDescriptionCode($codeFaker->unique()->word());
            $dish->setStatus($statuses[mt_rand(0, 2)]);

            foreach ($this->languages as $lang) {
                $this->createAndSaveTranslation($dish->getTitleCode(), $lang, $languageFakers[$lang]);
                $this->createAndSaveTranslation($dish->getDescriptionCode(), $lang, $languageFakers[$lang]);
            }

            if (mt_rand(0, 1)) {
                $dish->setCategory($this->createAndSaveCategory($codeFaker, $languageFakers));
            }

            $numOfTags = mt_rand(1, 4); // TODO - extract "magic numbers" to constants (multiple occurrences)
            for ($j = 0; $j < $numOfTags; $j++) {
                $dish->addTag($this->createAndSaveTag($codeFaker, $languageFakers));
            }

            $numOfIngredients = mt_rand(1, 3);
            for ($k = 0; $k < $numOfIngredients; $k++) {
                $dish->addIngredient($this->createAndSaveIngredient($codeFaker, $languageFakers));
            }

            $this->em->persist($dish);
        }
    }

    private function createAndSaveTranslation(string $code, string $lang, $langFaker): void
    {
        $translation = new Translation();
        $translation->setCode($code);
        $translation->setLanguageCode($lang);
        $translation->setTranslation($langFaker->name());

        $this->em->persist($translation);
    }

    private function createAndSaveCategory(Generator $codeFaker, array $languageFakers): Category
    {
        $category = new Category();
        $category->setSlug($codeFaker->slug());
        $category->setNameCode($codeFaker->unique()->city());

        $this->em->persist($category);

        foreach ($this->languages as $lang) {
            $this->createAndSaveTranslation($category->getNameCode(), $lang, $languageFakers[$lang]);
        }

        return $category;
    }

    private function createAndSaveTag(Generator $codeFaker, array $languageFakers): Tag
    {
        $tag = new Tag();
        $tag->setSlug($codeFaker->slug());
        $tag->setNameCode($codeFaker->unique()->word());

        $this->em->persist($tag);

        foreach ($this->languages as $lang) {
            $this->createAndSaveTranslation($tag->getNameCode(), $lang, $languageFakers[$lang]);
        }

        return $tag;
    }

    private function createAndSaveIngredient(Generator $codeFaker, array $languageFakers): Ingredient
    {
        $ingredient = new Ingredient();
        $ingredient->setSlug($codeFaker->slug());
        $ingredient->setNameCode($codeFaker->unique()->word());

        $this->em->persist($ingredient);

        foreach ($this->languages as $lang) {
            $this->createAndSaveTranslation($ingredient->getNameCode(), $lang, $languageFakers[$lang]);
        }

        return $ingredient;
    }
}
