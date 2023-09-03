<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Meal;
use App\Entity\Ingredient;
use App\Entity\Language;
use App\Entity\Status;
use App\Entity\Tag;
use App\Entity\Translation;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:populate-database',
    description: 'Populates database with fake data',
    aliases: ['app:fill-database', 'app:fill-db', 'app:populate-db'],
    hidden: false
)]
class PopulateDBCommand extends Command
{
    private EntityManagerInterface $em;
    private const MIN_TAGS_PER_MEAL = 1;
    private const MAX_TAGS_PER_MEAL = 4;
    private const LANGUAGES = ['hr_HR', 'en_EN', 'cs_CZ', 'de_DE', 'fr_FR'];
    private const NUM_OF_MEALS = 20;
    private const NUMBER_OF_CATEGORIES = 5;
    private const NUMBER_OF_TAGS = 7;
    private const NUMBER_OF_INGREDIENTS = 10;
    private const SHORT_CODE_LENGTH = 2;
    private array $categories = [];
    private array $ingredients = [];
    private array $tags = [];

    public function __construct(EntityManagerInterface $em, string $name = null)
    {
        $this->em = $em;

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->isPopulated()){
            $output->writeln('Database is already populated! First run clean command in order to populate again.');
            return Command::INVALID;
        }

        try {
            $output->writeln('Populating db...');

            $this->fillDatabase();

            $output->writeln('Database populated successfully.');
        } catch (\Exception $e) {
            $output->writeln('An error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function isPopulated(): bool
    {
        return $this->em->getRepository(Meal::class)->count([]) != 0;
    }

    private function fillDatabase(): void
    {
        $statuses = $this->createAndSaveStatuses();
        $this->createAndSaveLanguages();
        $languageFakers = $this->getLanguageFakers();

        $codeFaker = Factory::create();
        $codeFaker->seed(100);

        for($i = 0; $i < self::NUMBER_OF_CATEGORIES; $i++) {
            $this->categories[] = $this->createAndSaveCategory($codeFaker, $languageFakers);
        }

        for($i = 0; $i < self::NUMBER_OF_INGREDIENTS; $i++) {
            $this->ingredients[] = $this->createAndSaveIngredient($codeFaker, $languageFakers);
        }

        for($i = 0; $i < self::NUMBER_OF_TAGS; $i++) {
            $this->tags[] = $this->createAndSaveTag($codeFaker, $languageFakers);
        }

        $this->createAndSaveMeals($codeFaker, $statuses, $languageFakers);

        $this->em->flush();
    }

    private function createAndSaveStatuses(): array
    {
        $statusCreated = new Status();
        $statusCreated->setName('created');

        $statusModified = new Status();
        $statusModified->setName('modified');

        $statusDeleted = new Status();
        $statusDeleted->setName('deleted');

        $this->em->persist($statusModified);
        $this->em->persist($statusCreated);
        $this->em->persist($statusDeleted);

        return [$statusCreated, $statusDeleted, $statusModified];
    }

    private function createAndSaveLanguages(): void
    {
        foreach (self::LANGUAGES as $lang) {
            $language = new Language();
            $language->setCode($lang);
            $language->setShortCode(substr($lang, 0, 2));

            $this->em->persist($language);
        }
    }

    private function getLanguageFakers(): array
    {
        $languageFakers = [];
        foreach (self::LANGUAGES as $lang) {
            $langFaker = Factory::create($lang);
            $langFaker->seed(100);
            $languageFakers[$lang] = $langFaker;
        }

        return $languageFakers;
    }

    private function createAndSaveMeals(Generator $basicFaker, array $statuses, array $languageFakers): void
    {
        for ($i = 0; $i < self::NUM_OF_MEALS; $i++) {
            $meal = new Meal();

            $meal->setTitleCode($basicFaker->unique()->word());
            $meal->setDescriptionCode($basicFaker->unique()->word());
            $meal->setStatus($statuses[mt_rand(0, sizeof($statuses) - 1)]);
            $meal->setDateModified($basicFaker->dateTime());

            foreach (self::LANGUAGES as $lang) {
                $this->createAndSaveTranslation($meal->getTitleCode(), $lang, $languageFakers[$lang]);
                $this->createAndSaveTranslation($meal->getDescriptionCode(), $lang, $languageFakers[$lang]);
            }

            if (mt_rand(0, 1)) {
                $meal->setCategory($this->categories[mt_rand(0, self::NUMBER_OF_CATEGORIES - 1)]);
            }

            $numOfTags = mt_rand(self::MIN_TAGS_PER_MEAL, self::MAX_TAGS_PER_MEAL);
            for ($j = 0; $j < $numOfTags; $j++) {
                $meal->addTag($this->tags[mt_rand(0, self::NUMBER_OF_TAGS - 1)]);
            }

            $numOfIngredients = mt_rand(1, 3);
            for ($k = 0; $k < $numOfIngredients; $k++) {
                $meal->addIngredient($this->ingredients[mt_rand(0, self::NUMBER_OF_INGREDIENTS - 1)]);
            }

            $this->em->persist($meal);
        }
    }

    private function createAndSaveTranslation(string $code, string $lang, $langFaker): void
    {
        $translation = new Translation();
        $translation->setCode($code);
        $translation->setLanguageCode($lang);
        $translation->setShortCode(substr($lang, 0, self::SHORT_CODE_LENGTH));
        $translation->setTranslation($langFaker->name());

        $this->em->persist($translation);
    }

    private function createAndSaveCategory(Generator $basicFaker, array $languageFakers): Category
    {
        $category = new Category();
        $category->setSlug($basicFaker->slug());
        $category->setNameCode($basicFaker->unique()->city());

        $this->em->persist($category);

        foreach (self::LANGUAGES as $lang) {
            $this->createAndSaveTranslation($category->getNameCode(), $lang, $languageFakers[$lang]);
        }

        return $category;
    }

    private function createAndSaveTag(Generator $basicFaker, array $languageFakers): Tag
    {
        $tag = new Tag();
        $tag->setSlug($basicFaker->slug());
        $tag->setNameCode($basicFaker->unique()->word());

        $this->em->persist($tag);

        foreach (self::LANGUAGES as $lang) {
            $this->createAndSaveTranslation($tag->getNameCode(), $lang, $languageFakers[$lang]);
        }

        return $tag;
    }

    private function createAndSaveIngredient(Generator $basicFaker, array $languageFakers): Ingredient
    {
        $ingredient = new Ingredient();
        $ingredient->setSlug($basicFaker->slug());
        $ingredient->setNameCode($basicFaker->unique()->word());

        $this->em->persist($ingredient);

        foreach (self::LANGUAGES as $lang) {
            $this->createAndSaveTranslation($ingredient->getNameCode(), $lang, $languageFakers[$lang]);
        }

        return $ingredient;
    }

}
