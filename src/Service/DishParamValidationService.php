<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

class DishParamValidationService
{
    private const LANG_LENGTH = 2;
    private const WITH_PARAMS = ['tags', 'category', 'ingredients'];

    public function validate(Request $request): array
    {
        $errors = [];

        $query = $request->query;
        $lang = $query->get('lang');
        if ($lang == null) {
            $errors['lang'] = 'language (lang) parameter must be set!';
        } else if (!ctype_alpha($lang) || strlen($lang) != self::LANG_LENGTH) {
            $errors['lang'] = 'language (lang) parameter must be two characters long with no numeric characters';
        }

        $perPage = $query->get('per_page');
        if ($perPage && !ctype_digit($perPage)) {
            $errors['per_page'] = 'per_page parameter must represent integer';
        }

        $page = $query->get('page');
        if ($page && !ctype_digit($page)) {
            $errors['page'] = 'page parameter must represent integer';
        }

        $category = $query->get('category');
        if ($category && !ctype_digit($category)) {
            $errors['category'] = 'category identifier must represent integer';
        }

        $tags = $query->get('tags');
        if ($tags) {
            $tagValid = true;
            $tagArr = explode(',', $tags);
            foreach ($tagArr as $tag) {
                if (!ctype_digit($tag)) {
                    $tagValid = false;
                }
            }

            if (!$tagValid) {
                $errors['tags'] = 'tags parameter must be a comma-delimited list of integers';
            }
        }

        $with = $query->get('with');
        if ($with && array_diff(explode(',', $with), self::WITH_PARAMS) != null) {
            $errors['with'] = 'with parameter cannot include values other than [ingredients, tags, category]';
        }

        $diffTime = $query->get('diff_time');
        if ($diffTime && !is_numeric($diffTime)) {
            $errors['diff_time'] = 'diff_time parameter must be numeric';
        }

        return $errors;
    }
}