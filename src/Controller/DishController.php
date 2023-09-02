<?php

namespace App\Controller;

use App\Service\DishService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DishController extends AbstractController
{
    #[Route('/dishes')]
    public function getDishes(Request $request, DishService $dishService): Response
    {
        $dishes = $dishService->findDishes($request);
        return new Response($dishes);
    }

}
