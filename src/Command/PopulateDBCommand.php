<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Meal;
use App\Entity\Ingredient;
use App\Entity\Language;
use App\Entity\Status;
use App\Entity\Tag;
use App\Entity\Translation;
use App\Interface\service\FakeDataGeneratorInterface;
use App\Interface\service\MealFactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
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
    private const MIN_TAGS_PER_MEAL = 1;
    private const MAX_TAGS_PER_MEAL = 4;
    private const NUM_OF_MEALS = 20;
    private const NUMBER_OF_CATEGORIES = 5;
    private const NUMBER_OF_TAGS = 7;
    private const NUMBER_OF_INGREDIENTS = 10;
    private const SHORT_CODE_LENGTH = 2;
    private const AVAILABLE_STATUSES = ['created', 'modified', 'deleted'];
    private array $categories = [];
    private array $ingredients = [];
    private array $tags = [];

    public function __construct(private EntityManagerInterface $em,
                                private MealFactoryInterface $mealFactory,
                                private FakeDataGeneratorInterface $dataGenerator,
                                private array $languages = [],
                                string $name = null)
    {
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
        $this->createAndSaveStatuses();
        $this->createAndSaveLanguages();
        $this->createAndSaveCategories();
        $this->createAndSaveIngredients();
        $this->createAndSaveTags();

        $this->em->flush();

        $dateTimes = $this->dataGenerator->generateDateTimes(self::NUM_OF_MEALS);
        $uniqueCodes = $this->dataGenerator->generateUniqueWords(self::NUM_OF_MEALS * 2);
        $this->createAndSaveTranslations($uniqueCodes);

        $this->createAndSaveMeals($uniqueCodes, $dateTimes);

        $this->em->flush();
    }

    private function createAndSaveStatuses(): void
    {
        foreach (self::AVAILABLE_STATUSES as $statusName) {
            $status = new Status();
            $status->setName($statusName);

            $this->em->persist($status);
        }
    }

    private function createAndSaveLanguages(): void
    {
        foreach ($this->languages as $lang) {
            $language = new Language();
            $language->setCode($lang);
            $language->setShortCode(substr($lang, 0, 2));

            $this->em->persist($language);
        }
    }

    private function createAndSaveCategories(): void
    {
        for($i = 0; $i < self::NUMBER_OF_CATEGORIES; $i++) {
            $slug = $this->dataGenerator->generateUniqueSlug();
            $nameCode = $this->dataGenerator->generateUniqueCity();
            $this->categories[] = $this->createAndSaveCategory($slug, $nameCode);

            foreach ($this->languages as $lang) {
                $this->createAndSaveTranslation($nameCode, $lang);
            }
        }
    }

    private function createAndSaveIngredients(): void
    {
        for($i = 0; $i < self::NUMBER_OF_INGREDIENTS; $i++) {
            $slug = $this->dataGenerator->generateUniqueSlug();
            $nameCode = $this->dataGenerator->generateUniqueWord();
            $this->ingredients[] = $this->createAndSaveIngredient($slug, $nameCode);

            foreach($this->languages as $lang) {
                $this->createAndSaveTranslation($nameCode, $lang);
            }
        }
    }

    private function createAndSaveTags(): void
    {
        for($i = 0; $i < self::NUMBER_OF_TAGS; $i++) {
            $slug = $this->dataGenerator->generateUniqueSlug();
            $nameCode = $this->dataGenerator->generateUniqueWord();
            $this->tags[] = $this->createAndSaveTag($slug, $nameCode);

            foreach($this->languages as $lang) {
                $this->createAndSaveTranslation($nameCode, $lang);
            }
        }
    }

    private function createAndSaveMeals(array $uniqueCodes, array $dateTimes): void
    {
        for ($i = 0; $i < self::NUM_OF_MEALS * 2; $i++) {
            $titleCode = $uniqueCodes[$i];
            $descCode = $uniqueCodes[$i];

            $statusName = self::AVAILABLE_STATUSES[mt_rand(0, sizeof(self::AVAILABLE_STATUSES) - 1)];
            $status = $this->em->getRepository(Status::class)->findOneBy(['name' => $statusName]);

            $dateModified = $dateTimes[$i % 10];
            $category = mt_rand(0, 1) == 1 ?  $this->categories[mt_rand(0, self::NUMBER_OF_CATEGORIES - 1)] : null;

            $meal = $this->mealFactory->create($titleCode, $descCode, $status, $dateModified, $category);

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

    private function createAndSaveTranslation(string $code, string $lang): void
    {
        $translation = new Translation();
        $translation->setCode($code);
        $translation->setLanguageCode($lang);
        $translation->setShortCode(substr($lang, 0, self::SHORT_CODE_LENGTH));
        $translation->setTranslation($this->dataGenerator->generateNameInLanguage($lang));

        $this->em->persist($translation);
    }

    private function createAndSaveCategory(string $slug, string $nameCode): Category
    {
        $category = new Category();
        $category->setSlug($slug);
        $category->setNameCode($nameCode);

        $this->em->persist($category);

        return $category;
    }

    private function createAndSaveTag(string $slug, string $nameCode): Tag
    {
        $tag = new Tag();
        $tag->setSlug($slug);
        $tag->setNameCode($nameCode);

        $this->em->persist($tag);

        return $tag;
    }

    private function createAndSaveIngredient(string $slug, string $nameCode): Ingredient
    {
        $ingredient = new Ingredient();
        $ingredient->setSlug($slug);
        $ingredient->setNameCode($nameCode);

        $this->em->persist($ingredient);

        return $ingredient;
    }

    private function createAndSaveTranslations(array $codes): void
    {
        foreach($codes as $code) {
            foreach ($this->languages as $lang) {
                $this->createAndSaveTranslation($code, $lang);
                $this->createAndSaveTranslation($code, $lang);
            }
        }
    }

}
