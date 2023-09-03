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
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:clean-database',
    description: 'Clears database, i.e. deletes all entries in tables',
    aliases: ['app:clear-database', 'app:clear-db', 'app:clean-db'],
    hidden: false
)]
class CleanDBCommand extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, string $name = null)
    {
        $this->em = $em;

        parent::__construct($name);
    }

    // TODO - replace manual deletion with cascade delete?
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $output->writeln('Clearing database...');

            $this->em->getRepository(Meal::class)->createQueryBuilder('d')->delete()->getQuery()->execute();
            $this->em->getRepository(Category::class)->createQueryBuilder('c')->delete()->getQuery()->execute();
            $this->em->getRepository(Tag::class)->createQueryBuilder('c')->delete()->getQuery()->execute();
            $this->em->getRepository(Ingredient::class)->createQueryBuilder('c')->delete()->getQuery()->execute();
            $this->em->getRepository(Language::class)->createQueryBuilder('c')->delete()->getQuery()->execute();
            $this->em->getRepository(Status::class)->createQueryBuilder('c')->delete()->getQuery()->execute();
            $this->em->getRepository(Translation::class)->createQueryBuilder('c')->delete()->getQuery()->execute();

            $this->em->getConnection()->executeQuery('delete from meal_tag');
            $this->em->getConnection()->executeQuery('delete from meal_ingredient');

            $output->writeln('Database cleared.');
        } catch (Exception $e) {
            $output->writeln('An error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

}
