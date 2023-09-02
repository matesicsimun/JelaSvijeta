<?php

namespace App\Controller;

use App\Service\DishService;
use App\Service\DishParamValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DishController extends AbstractController
{
    #[Route('/dishes')]
    public function getDishes(Request $request, DishService $dishService, DishParamValidationService $paramValidationService): Response
    {
        $errors = $paramValidationService->validate($request);
        if ($errors) {
            return new Response(json_encode(['status' => 400, 'errors' => $errors]));
        } else {
            return new Response($dishService->findDishes($request));
        }
    }

}
