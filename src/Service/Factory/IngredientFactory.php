<?php

namespace App\Service\Factory;

use App\Entity\Ingredient;
use App\Interface\service\IngredientFactoryInterface;

class IngredientFactory implements IngredientFactoryInterface
{

    public function create(string $slug, string $nameCode): Ingredient
    {
        $ingredient = new Ingredient();
        $ingredient->setSlug($slug);
        $ingredient->setNameCode($nameCode);

        return $ingredient;
    }
}