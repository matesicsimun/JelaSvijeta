<?php

namespace App\Interface\service;

use App\Entity\Tag;

interface TagFactoryInterface
{
    public function create(string $slug, string $nameCode): Tag;
}
