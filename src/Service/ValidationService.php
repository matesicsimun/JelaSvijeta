<?php

namespace App\Service;

use App\Entity\Language;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class ValidationService
{
    private EntityManagerInterface $em;
    private const LANG_LENGTH = 2;
    private const WITH_PARAMS = ['tags', 'category', 'ingredients'];

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function validate(Request $request): array
    {
        $errors = [];

        $query = $request->query;
        $lang = $query->get('lang');
        if ($lang == null) {
            $errors['lang'] = 'language (lang) parameter must be set!';
        } else if (!ctype_alpha($lang) || strlen($lang) != self::LANG_LENGTH) {
            $errors['lang'] = 'language (lang) parameter must be two characters long with no numeric characters';
        } else {
            $specifiedLanguage = $this->em->getRepository(Language::class)->findBy(['shortCode' => $lang]);
            if ($specifiedLanguage == null) {
                $errors['lang'] = 'language must be one of specified languages';
            }
        }

        $perPage = $query->get('per_page');
        if ($perPage != null && (!ctype_digit($perPage) || $perPage <= 0)) {
            $errors['per_page'] = 'per_page parameter must represent a positive integer';
        }

        $page = $query->get('page');
        if ($page != null && (!ctype_digit($page) || $page <= 0)) {
            $errors['page'] = 'page parameter must represent a positive integer';
        }

        $category = $query->get('category');
        if ($category != null && (!ctype_digit($category) || $category <= 0) && $category != 'NULL' && $category != '!NULL') {
            $errors['category'] = 'category identifier must represent a positive integer';
        }

        $tags = $query->get('tags');
        if ($tags != null) {
            $tagValid = true;
            $tagArr = explode(',', $tags);
            foreach ($tagArr as $tag) {
                if (!ctype_digit($tag) || $tag <= 0) {
                    $tagValid = false;
                }
            }

            if (!$tagValid) {
                $errors['tags'] = 'tags parameter must be a comma-delimited list of positive integers';
            }
        }

        $with = $query->get('with');
        if ($with != null && array_diff(explode(',', $with), self::WITH_PARAMS) != null) {
            $errors['with'] = 'with parameter cannot include values other than [ingredients, tags, category]';
        }

        $diffTime = $query->get('diff_time');
        if ($diffTime != null && (!is_numeric($diffTime) || $diffTime <= 0)) {
            $errors['diff_time'] = 'diff_time parameter must be numeric and positive';
        }

        return $errors;
    }
}