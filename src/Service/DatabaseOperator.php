<?php

namespace App\Service;

use App\Entity\Language;
use App\Entity\Status;
use Doctrine\ORM\EntityManagerInterface;

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

        $statusDeactivated = new Status();
        $statusDeactivated->setName('deactivated');
        $this->em->persist($statusDeactivated);

        $statusDeleted = new Status();
        $statusDeleted->setName('deleted');
        $this->em->persist($statusDeleted);

        $this->em->flush();

        //languages
        $languages = ['hr_HR', 'en_EN', 'cs_CZ', 'de_DE', 'fr_FR'];
        foreach ($languages as $lang) {
            $language = new Language();
            $language->setCode($lang);
            $this->em->persist($language);
        }

        $this->em->flush();
    }

    public function cleanupDatabase(): void
    {
        $this->em->createQuery(
            'DELETE FROM App\Entity\Status'
        )->execute();

        $this->em->createQuery(
            'DELETE FROM App\Entity\Language'
        )->execute();
    }
}