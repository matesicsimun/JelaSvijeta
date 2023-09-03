<?php

namespace App\Service;

use App\Converter\CodeNameConverter;
use App\Entity\Dish;
use App\Entity\Status;
use App\Entity\Translation;
use App\Repository\TranslationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class DishService
{
    private EntityManagerInterface $em;
    private const STATUS_CREATED = 'created';
    private const DEFAULT_LANG = 'en';
    private const DEFAULT_PAGE_ITEMS = 10;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getDishes(Request $request): array
    {
        $params = $this->getQueryParams($request);
        $query = $this->createQuery($params);

        $totalDishesMatchingCriteria = sizeof($query->getResult());

        $currentPageNumber = $params['page'] ?: 1;
        $itemsPerPage = $request->query->get('per_page') ?: self::DEFAULT_PAGE_ITEMS;

        $dishes = $this->createPaginator($query, $params, $itemsPerPage)
                        ->getQuery()
                        ->getResult();

        $totalPages = ceil($totalDishesMatchingCriteria / $itemsPerPage);

        $links = $this->createPaginationLinks($request, $currentPageNumber, $totalPages);
        $data = $this->createDishData($dishes, $params['lang'], $params['with']);
        $meta = $this->createMetaDataEntry($currentPageNumber, $itemsPerPage, $totalDishesMatchingCriteria);

        return ['meta' => $meta, 'data' => $data, 'links' => $links];
    }

    private function createQuery(array $params): Query
    {
        $qb = $this->em->getRepository(Dish::class)->createQueryBuilder('o');
        $qbParams = [];

        if ($params['diffTime'] != null) {
            $this->addDiffTimeCriteria($qb, $params, $qbParams);
        } else {
            $this->addStatusCreatedCriteria($qb, $qbParams);
        }

        if ($params['tags'] != null) {
            $this->addTagsCriteria($qb, $params, $qbParams);
        }

        if ($params['category'] != null) {
            $this->addCategoryCriteria($qb, $params, $qbParams);
        }

        $qb->setParameters($qbParams);
        return $qb->getQuery();
    }

    private function addDiffTimeCriteria(QueryBuilder $qb, array $params, array &$dbQueryParams): void
    {
        $date = new \DateTime();
        $date->setTimestamp($params['diffTime']);
        $qb->where('o.dateModified > :diffTime');
        $dbQueryParams['diffTime'] = $date;
    }

    private function addStatusCreatedCriteria(QueryBuilder $qb, array &$qbParams): void
    {
        $statusCreated = $this->em->getRepository(Status::class)->findOneBy(['name' => self::STATUS_CREATED]);
        $qb->where('o.status = :statusId');
        $qbParams = ['statusId' => $statusCreated->getId()];
    }

    private function addTagsCriteria(QueryBuilder $qb, array $params, array &$qbParams): void
    {
        $tagIds = explode(',', $params['tags']);
        $tagParamNum = 1;
        foreach($tagIds as $tagId) {
            $tagAlias = 't'.$tagParamNum;
            $qb->innerJoin('o.tags', $tagAlias, 'WITH', $tagAlias.'.id = :tagId'.$tagParamNum);
            $qbParams['tagId'.$tagParamNum] = $tagId;
            $tagParamNum++;
        }
    }

    private function addCategoryCriteria(QueryBuilder $qb, array $params, array &$dbQueryParams): void
    {
        $categoryId = $params['category'];
        if ($categoryId == 'NULL') {
            $qb->andWhere($qb->expr()->isNull('o.category'));
        } else if ($categoryId == '!NULL') {
            $qb->andWhere($qb->expr()->isNotNull('o.category'));
        } else {
            $qb->andWhere('o.category = :categoryId');
            $dbQueryParams['categoryId'] = $categoryId;
        }
    }

    private function createPaginator(Query $query, array $params, int $itemsPerPage): Paginator
    {
        $paginator = new Paginator($query, $fetchJoinCollection = true);
        $paginator->getQuery()->setMaxResults($itemsPerPage);
        if ($params['page'] != null) {
            $currentPageNumber = $params['page'];
            $paginator->getQuery()->setFirstResult($itemsPerPage * ($currentPageNumber - 1));
        }

        return $paginator;
    }

    private function createPaginationLinks(Request $request, int $currentPage, int $totalPages): array
    {
        $prevReq = ($currentPage == 1) ? null : Request::create($request->getUri(), 'GET', ['page' => $currentPage - 1]);
        $nextReq = ($currentPage >= $totalPages) ? null : Request::create($request->getUri(), 'GET', ['page' => $currentPage + 1]);
        return [
            'prev' => urldecode($prevReq?->getUri()),
            'next' => urldecode($nextReq?->getUri()),
            'self' => urldecode($request->getUri())
        ];
    }

    private function createMetaDataEntry(int $page, int $itemsPerPage, int $total): array
    {
        return [
            'currentPage' => $page,
            'totalItems' => $total,
            'itemsPerPage' => $itemsPerPage,
            'totalPages' => ceil($total / $itemsPerPage)
        ];
    }

    private function getQueryParams(Request $request): array
    {
        $query = $request->query;
        return [
            'lang' => $query->get('lang') ?: self::DEFAULT_LANG,
            'diffTime' => $query->get('diff_time'),
            'tags' => $query->get('tags'),
            'category' => $query->get('category'),
            'perPage' => $query->get('per_page'),
            'page' => $query->get('page'),
            'with' => explode(',', $query->get('with')) ?: []
        ];
    }

    private function createDishData(array $dishes, string $lang, array $with = []): array
    {
        $encoders = [new JsonEncoder()];
        $codeNameConverter = new CodeNameConverter();
        $normalizers = [new ObjectNormalizer(null, $codeNameConverter)];
        $serializer = new Serializer($normalizers, $encoders);

        $attributes = ['tags', 'ingredients', 'category'];
        $ignoredAttributes = array_merge(['dateModified'], array_diff($attributes, $with));

        $foundDishesJson = $serializer->serialize($dishes, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => $ignoredAttributes]);

        return $this->prepareData($foundDishesJson, $lang, $with);
    }

    private function prepareData(string $foundDishesJson, string $lang, array $with): array {
        $jsonDecoded = json_decode($foundDishesJson, true);
        $translationRepo = $this->em->getRepository(Translation::class);

        foreach($jsonDecoded as &$dishEntry) {
            $this->translate($translationRepo, $lang, $dishEntry['title']);
            $this->translate($translationRepo, $lang, $dishEntry['description']);
            $dishEntry['status'] = $dishEntry['status']['title'];

            if (in_array('category', $with) && key_exists('category', $dishEntry) && $dishEntry['category'] != null) {
                $this->translate($translationRepo, $lang, $dishEntry['category']['title']);
            }

            if (in_array('tags', $with)) {
                foreach($dishEntry['tags'] as &$dishEntryTags) {
                    $this->translate($translationRepo, $lang, $dishEntryTags['title']);
                }
            }

            if (in_array('ingredients', $with)) {
                foreach($dishEntry['ingredients'] as &$ingredient) {
                    $this->translate($translationRepo, $lang, $ingredient['title']);
                }
            }
        }

        return $jsonDecoded;
    }

    private function translate(TranslationRepository $repo, string $lang, string &$code): void
    {
        $code = $repo->findOneBy(array_merge(['shortCode' => $lang], ['code' => $code]))->getTranslation();
    }

}
