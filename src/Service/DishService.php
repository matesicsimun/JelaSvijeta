<?php

namespace App\Service;

use App\Entity\Status;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class DishService
{
    private EntityManagerInterface $em;
    private string $DEFAULT_STATUS = 'active';
    private int $DEFAULT_PAGE_ITEMS = 10;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
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
        $json = $this->transformToJson($dishes, $request->query->get('with'));

        return $json;
    }

    public function transformToJson(array $dishes, array $with = null) {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        return $serializer->serialize($dishes, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['dateModified']]);
        //return $serializer->serialize($serializer->normalize($dishes), 'json');
    }
}
