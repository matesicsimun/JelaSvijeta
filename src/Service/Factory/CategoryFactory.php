<?php

namespace App\Service\Factory;

use App\Entity\Category;
use App\Interface\service\CategoryFactoryInterface;

class CategoryFactory implements CategoryFactoryInterface
{

    public function create(string $slug, string $nameCode): Category
    {
        $category = new Category();
        $category->setSlug($slug);
        $category->setNameCode($nameCode);

        return $category;
    }
}