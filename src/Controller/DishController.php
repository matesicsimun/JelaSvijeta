<?php

namespace App\Controller;

use App\Service\DishService;
use App\Service\ValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DishController extends AbstractController
{
    #[Route('/dishes')]
    public function getDishes(Request $request, DishService $dishService, ValidationService $validationService): Response
    {
        $errors = $validationService->validate($request);
        if ($errors) {
            return new Response(json_encode(['status' => 400, 'errors' => $errors]));
        } else {
            return new Response(json_encode($dishService->getDishes($request)));
        }
    }

}
