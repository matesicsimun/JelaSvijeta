<?php

namespace App\Service\Factory;

use App\Entity\Category;
use App\Entity\Meal;
use App\Entity\Status;
use App\Interface\model\MealInterface;
use App\Interface\service\MealFactoryInterface;
use Doctrine\Common\Collections\ArrayCollection;

class MealFactory implements MealFactoryInterface
{

    public function create(string $titleCode, string $descriptionCode, Status $status, \DateTimeInterface $dateModified, Category $category = null): MealInterface
    {
        $meal = new Meal();
        $meal->setTitleCode($titleCode);
        $meal->setDescriptionCode($descriptionCode);
        $meal->setStatus($status);
        $meal->setCategory($category);
        $meal->setDateModified($dateModified);

        return $meal;
    }
}