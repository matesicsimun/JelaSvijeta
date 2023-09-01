<?php

namespace App\Controller;

use App\Service\DatabaseInitializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DbInitController extends AbstractController
{

    #[Route('/fillDB')]
    public function fillDB(DatabaseInitializer $dbOperator): Response
    {
        $dbOperator->fillDatabase();

        return new Response('Baza je popunjena.');
    }

    #[Route('/cleanupDB')]
    public function cleanupDB(DatabaseInitializer $dbOperator): Response
    {
        $dbOperator->cleanupDatabase();

        return new Response('Baza je očišćena.');
    }
}