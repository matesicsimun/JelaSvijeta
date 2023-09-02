<?php

namespace App\Service;

use App\Converter\CodeNameConverter;
use App\Entity\Dish;
use App\Entity\Status;
use App\Entity\Translation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class DishService
{
    private EntityManagerInterface $em;
    private string $DEFAULT_STATUS = 'active';
    private string $DEFAULT_LANG = 'en';
    private int $DEFAULT_PAGE_ITEMS = 10;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function validateParams(Request $request): array
    {

    }

    // TODO - extract code to repository impl?
    // TODO - add param validation & sanitization
    // TODO - replace request with just params (service should not depend on request)
    public function findDishes(Request $request): string
    {
        $defaultStatus = $this->em->getRepository(Status::class)->findOneBy(['name' => $this->DEFAULT_STATUS]);

        $qb = $this->em->getRepository('App\Entity\Dish')->createQueryBuilder('o');

        $params = [];
        if ($request->query->get('diff_time') != null) {
            $diffTime = $request->query->get('diff_time');
            $date = new \DateTime();
            $date->setTimestamp($diffTime);
            $qb->where('o.dateModified > :diffTime');
            $params['diffTime'] = $date;
        } else {
            $qb->where('o.status = :statusId');
            $params = ['statusId' => $defaultStatus->getId()];
        }

        if ($request->query->get('tags') != null) {
            $tagIds = explode(',', $request->query->get('tags'));
            $tagParamNum = 1;
            foreach($tagIds as $tagId) {
                $tagAlias = 't'.$tagParamNum;
                $qb->innerJoin('o.tags', $tagAlias, 'WITH', $tagAlias.'.id = :tagId'.$tagParamNum);
                $params['tagId'.$tagParamNum] = $tagId;
                $tagParamNum++;
            }
        }

        if ($request->query->get('category') != null) {
            $categoryId = $request->query->get('category');
            if ($categoryId == 'NULL') {
                $qb->andWhere($qb->expr()->isNull('o.category'));
            } else if ($categoryId == '!NULL') {
                $qb->andWhere($qb->expr()->isNotNull('o.category'));
            } else {
                $qb->andWhere('o.category = :categoryId');
                $params['categoryId'] = $categoryId;
            }
        }

        $qb->setParameters($params);

        $query = $qb->getQuery();

        $paginator = new Paginator($query, $fetchJoinCollection = true);

        $itemsPerPage = $request->query->get('per_page') ?: $this->DEFAULT_PAGE_ITEMS;
        $paginator->getQuery()->setMaxResults($itemsPerPage);

        if ($request->query->get('page') != null) {
            $pageNum = $request->query->get('page');
            $paginator->getQuery()->setFirstResult($itemsPerPage * ($pageNum - 1));
        }

        $dishes = $paginator->getQuery()->getResult();

        $lang = $request->query->get('lang') ?: $this->DEFAULT_LANG;

        return $this->transformToJson($dishes, $lang, explode(',', $request->query->get('with')) ?: []);
    }

    public function transformToJson(array $dishes, string $lang, array $with = []): string
    {
        $encoders = [new JsonEncoder()];
        $codeNameConverter = new CodeNameConverter();
        $normalizers = [new ObjectNormalizer(null, $codeNameConverter)];
        $serializer = new Serializer($normalizers, $encoders);

        $this->translate($dishes, $lang);

        $attributes = ['tags', 'ingredients', 'category'];
        $ignoredAttributes = array_merge(['dateModified'], array_diff($attributes, $with));

        /*foreach($dishes as $dish) {
            $this->createTranslatedJson($dish, $lang);
        }*/

        return $serializer->serialize($dishes, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => $ignoredAttributes]);
    }

    // TODO - instead of changing existing objects, perhaps create DTO's
    private function translate(array &$dishes, string $lang, array $with): void
    {
        $translationRepo = $this->em->getRepository(Translation::class);
        foreach ($dishes as $dish) {
            $title = $translationRepo->findOneBy(['shortCode' => $lang, 'code' => $dish->getTitleCode()])->getTranslation();
            $dish->setTitleCode($title);

            $desc = $translationRepo->findOneBy(['shortCode' => $lang, 'code' => $dish->getDescriptionCode()])->getTranslation();
            $dish->setDescriptionCode($desc);

            // TODO translate other things in the json...
        }
    }

    private function createTranslatedJson(Dish $dish, $lang): string
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        return '';
    }
}
