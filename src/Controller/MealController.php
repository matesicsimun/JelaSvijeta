<?php

namespace App\Controller;

use App\Service\MealService;
use App\Service\ValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MealController extends AbstractController
{
    #[Route('/meals')]
    public function getMeals(Request $request, MealService $mealService, ValidationService $validationService): JsonResponse
    {
        $errors = $validationService->validate($request);
        if ($errors) {
            return new JsonResponse(['status' => 400, 'errors' => $errors]);
        } else {
            return new JsonResponse($mealService->getMeals($request));
        }
    }

}
