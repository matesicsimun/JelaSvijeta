<?php

namespace App\Controller;

use App\Service\MealService;
use App\Service\ValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MealController extends AbstractController
{
    #[Route('/meals')]
    public function getMeals(Request $request, MealService $mealService, ValidationService $validationService): Response
    {
        $errors = $validationService->validate($request);
        if ($errors) {
            return new Response(json_encode(['status' => 400, 'errors' => $errors]));
        } else {
            return new Response(json_encode($mealService->getMeals($request)));
        }
    }

}
