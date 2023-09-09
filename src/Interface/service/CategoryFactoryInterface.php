<?php

namespace App\Interface\service;

use App\Entity\Category;

interface CategoryFactoryInterface
{
    public function create(string $slug, string $nameCode): Category;
}
