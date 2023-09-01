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

class DatabaseInitializer
{
    private EntityManagerInterface $em;
    private int $MIN_TAGS_PER_DISH = 1;
    private int $MAX_TAGS_PER_DISH = 4;
    private array $languages = ['hr_HR', 'en_EN', 'cs_CZ', 'de_DE', 'fr_FR'];
    private int $numberOfCategories = 5;
    private array $categories = [];
    private int $numberOfIngredients = 10;
    private array $ingredients = [];
    private int $numberOfTags = 7;
    private array $tags = [];

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

        // TODO - perhaps don't make categories, ingredients and tags a property of the service (anti-pattern ?)
        for($i = 0; $i < $this->numberOfCategories; $i++) {
            $this->categories[] = $this->createAndSaveCategory($codeFaker, $languageFakers);
        }

        for($i = 0; $i < $this->numberOfIngredients; $i++) {
            $this->ingredients[] = $this->createAndSaveIngredient($codeFaker, $languageFakers);
        }

        for($i = 0; $i < $this->numberOfTags; $i++) {
            $this->tags[] = $this->createAndSaveTag($codeFaker, $languageFakers);
        }

        $this->createAndSaveDishes($codeFaker, $statuses, $languageFakers);

        $this->em->flush();
    }

    public function cleanupDatabase(): void
    {
        //TODO - delete properly (with cascading opt)
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
                $dish->setCategory($this->categories[mt_rand(0, $this->numberOfCategories - 1)]);
            }

            $numOfTags = mt_rand($this->MIN_TAGS_PER_DISH, $this->MAX_TAGS_PER_DISH);
            for ($j = 0; $j < $numOfTags; $j++) {
                $dish->addTag($this->tags[mt_rand(0, $this->numberOfTags - 1)]);
            }

            $numOfIngredients = mt_rand(1, 3);
            for ($k = 0; $k < $numOfIngredients; $k++) {
                $dish->addIngredient($this->ingredients[mt_rand(0, $this->numberOfIngredients - 1)]);
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
