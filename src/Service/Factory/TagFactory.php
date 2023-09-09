<?php

namespace App\Service\Factory;

use App\Entity\Tag;
use App\Interface\service\TagFactoryInterface;

class TagFactory implements TagFactoryInterface
{

    public function create(string $slug, string $nameCode): Tag
    {
        $tag = new Tag();
        $tag->setSlug($slug);
        $tag->setNameCode($nameCode);

        return $tag;
    }
}