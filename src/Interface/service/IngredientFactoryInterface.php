<?php

namespace App\Interface\service;

use App\Entity\Ingredient;

interface IngredientFactoryInterface
{
    public function create(string $slug, string $nameCode): Ingredient;
}
