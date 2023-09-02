<?php

namespace App\Service;

use App\Converter\CodeNameConverter;
use App\Dto\DishDto;
use App\Entity\Dish;
use App\Entity\Status;
use App\Entity\Translation;
use Doctrine\Common\Collections\ArrayCollection;
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
    private const DEFAULT_STATUS = 'active';
    private const DEFAULT_LANG = 'en';
    private const DEFAULT_PAGE_ITEMS = 10;

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
        $defaultStatus = $this->em->getRepository(Status::class)->findOneBy(['name' => self::DEFAULT_STATUS]);

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

        $itemsPerPage = $request->query->get('per_page') ?: self::DEFAULT_PAGE_ITEMS;
        $paginator->getQuery()->setMaxResults($itemsPerPage);

        if ($request->query->get('page') != null) {
            $pageNum = $request->query->get('page');
            $paginator->getQuery()->setFirstResult($itemsPerPage * ($pageNum - 1));
        }

        $dishes = $paginator->getQuery()->getResult();

        $lang = $request->query->get('lang') ?: self::DEFAULT_LANG;

        return $this->transformToJson($dishes, $lang, explode(',', $request->query->get('with')) ?: []);
    }

    public function transformToJson(array $dishes, string $lang, array $with = []): string
    {
        $encoders = [new JsonEncoder()];
        $codeNameConverter = new CodeNameConverter();
        $normalizers = [new ObjectNormalizer(null, $codeNameConverter)];
        $serializer = new Serializer($normalizers, $encoders);

        $attributes = ['tags', 'ingredients', 'category'];
        $ignoredAttributes = array_merge(['dateModified'], array_diff($attributes, $with));

        $json = $serializer->serialize($dishes, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => $ignoredAttributes]);

        return $this->prepareJson($json, $lang, $with);
    }

    private function prepareJson(string $json, string $lang, array $with): string {
        $jsonDecoded = json_decode($json, true);
        $translationRepo = $this->em->getRepository(Translation::class);
        foreach($jsonDecoded as &$dishEntry) {
            $dishEntry['title'] = $translationRepo->findOneBy(['shortCode' => $lang, 'code' => $dishEntry['title']])->getTranslation();
            $dishEntry['description'] = $translationRepo->findOneBy(['shortCode' => $lang, 'code' => $dishEntry['description']])->getTranslation();
            $dishEntry['status'] = $dishEntry['status']['name'];

            if (in_array('category', $with) && key_exists('category', $dishEntry) && $dishEntry['category'] != null) {
                $dishEntry['category']['name'] = $translationRepo->findOneBy(['shortCode' => $lang, 'code' => $dishEntry['category']['name']])->getTranslation();
            }

            if (in_array('tags', $with)) {
                foreach($dishEntry['tags'] as &$dishEntryTags) {
                    $dishEntryTags['name'] = $translationRepo->findOneBy(['shortCode' => $lang, 'code' => $dishEntryTags['name']])->getTranslation();
                }
            }

            if (in_array('ingredients', $with)) {
                foreach($dishEntry['ingredients'] as &$ingredient) {
                    $ingredient['name'] = $translationRepo->findOneBy(['shortCode' => $lang, 'code' => $ingredient['name']])->getTranslation();
                }
            }
        }

        return json_encode($jsonDecoded);
    }

}
