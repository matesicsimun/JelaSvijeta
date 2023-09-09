<?php

namespace App\Interface\model;

use App\Entity\Category;
use App\Entity\Ingredient;
use App\Entity\Status;
use App\Entity\Tag;
use Doctrine\Common\Collections\Collection;

interface MealInterface
{
    public function getCategory(): ?Category;

    public function setCategory(Category $category): static;

    public function getIngredients(): Collection;

    public function addIngredient(Ingredient $ingredient): static;

    public function getTags(): Collection;

    public function addTag(Tag $tag): static;

    public function getTitleCode(): ?string;

    public function setTitleCode(string $titleCode): static;

    public function getDescriptionCode(): ?string;

    public function setDescriptionCode(string $descriptionCode): static;

    public function getStatus(): ?Status;

    public function setStatus(Status $status): static;

    public function getDateModified(): ?\DateTimeInterface;

    public function setDateModified(\DateTimeInterface $dateTime): static;
}