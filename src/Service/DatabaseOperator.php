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

class DatabaseOperator
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function fillDatabase(): void
    {
        // TODO add check if DB is already filled

        //status
        $statusActive = new Status();
        $statusActive->setName('active');
        $this->em->persist($statusActive);

        $statusModified = new Status();
        $statusModified->setName('modified');
        $this->em->persist($statusModified);

        $statusDeleted = new Status();
        $statusDeleted->setName('deleted');
        $this->em->persist($statusDeleted);
        
        $statuses = [$statusActive, $statusDeleted, $statusModified];

        $this->em->flush();

        //languages
        $languages = ['hr_HR', 'en_EN', 'cs_CZ', 'de_DE', 'fr_FR'];
        $languageFakers = [];
        foreach ($languages as $lang) {
            $language = new Language();
            $language->setCode($lang);
            $this->em->persist($language);

            //create language faker
            $langFaker = Factory::create($lang);
            $langFaker->seed(100);
            $languageFakers[$lang] = $langFaker;
        }

        $this->em->flush();

        // create general faker for codes
        $faker = Factory::create();
        $faker->seed(100);

        //dishes
        $numOfDishes = 20;
        for ($i = 0; $i < $numOfDishes; $i++) {
            $dish = new Dish();
            $dish->setTitleCode($faker->unique()->word());
            $dish->setDescriptionCode($faker->unique()->word());
            $dish->setStatus($statuses[mt_rand(0, 2)]);

            // create translations for dish title and description
            foreach ($languages as $lang) {
                $titleTranslation = new Translation();
                $titleTranslation->setCode($dish->getTitleCode());
                $titleTranslation->setLanguageCode($lang);
                $titleTranslation->setTranslation($languageFakers[$lang]->name());

                $descTranslation = new Translation();
                $descTranslation->setCode($dish->getDescriptionCode());
                $descTranslation->setLanguageCode($lang);
                $descTranslation->setTranslation($languageFakers[$lang]->name());

                $this->em->persist($titleTranslation);
                $this->em->persist($descTranslation);
            }

            // create and set category if needed
            if (mt_rand(0, 1)) {
                $category = new Category();
                $category->setSlug($faker->slug());
                $category->setNameCode($faker->unique()->city());

                $this->em->persist($category);

                // create translations for this categories' name code
                foreach ($languages as $lang) {
                    $translation = new Translation();
                    $translation->setCode($category->getNameCode());
                    $translation->setLanguageCode($lang);

                    $langFaker = $languageFakers[$lang];
                    $translation->setTranslation($langFaker->name());

                    $this->em->persist($translation);
                }

                $dish->setCategory($category);
            }

            // create and set tags (at least one)
            $numOfTags = mt_rand(1, 4); // TODO - extract "magic numbers" to constants (multiple occurrences)
            for ($j = 0; $j < $numOfTags; $j++) {
                $tag = new Tag();
                $tag->setSlug($faker->slug());
                $tag->setNameCode($faker->unique()->word());

                $this->em->persist($tag);

                $dish->addTag($tag);

                //create translations for this tag's name code
                foreach ($languages as $lang) {
                    $translation = new Translation();
                    $translation->setCode($tag->getNameCode());
                    $translation->setLanguageCode($lang);

                    $langFaker = $languageFakers[$lang];
                    $translation->setTranslation($langFaker->name());

                    $this->em->persist($translation);
                }
            }

            $numOfIngredients = mt_rand(1, 3);
            for ($k = 0; $k < $numOfIngredients; $k++) {
                $ingredient = new Ingredient();
                $ingredient->setSlug($faker->slug());
                $ingredient->setNameCode($faker->unique()->word());

                $this->em->persist($ingredient);

                $dish->addIngredient($ingredient);

                foreach ($languages as $lang) {
                    $translation = new Translation();
                    $translation->setCode($ingredient->getNameCode());
                    $translation->setLanguageCode($lang);

                    $langFaker = $languageFakers[$lang];
                    $translation->setTranslation($langFaker->name());

                    $this->em->persist($translation);
                }
            }
            $this->em->persist($dish);
        }
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
}