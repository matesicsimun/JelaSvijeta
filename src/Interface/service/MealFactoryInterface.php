<?php

namespace App\Interface\service;

use App\Entity\Category;
use App\Entity\Status;
use App\Interface\model\MealInterface;

interface MealFactoryInterface
{
    public function create(
        string $titleCode,
        string $descriptionCode,
        Status $status,
        \DateTimeInterface $dateModified,
        Category $category = null
    ): MealInterface;
}