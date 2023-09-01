<?php

namespace App\Controller;

use App\Service\DatabaseOperator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DatabaseOperationsController extends AbstractController
{

    #[Route('/fillDB')]
    public function fillDB(DatabaseOperator $dbOperator): Response
    {
        $dbOperator->fillDatabase();

        return new Response('Baza je popunjena.');
    }

    #[Route('/cleanupDB')]
    public function cleanupDB(DatabaseOperator $dbOperator): Response
    {
        $dbOperator->cleanupDatabase();

        return new Response('Baza je očišćena.');
    }
}