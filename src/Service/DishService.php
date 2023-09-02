<?php

namespace App\Service;

use App\Entity\Dish;
use App\Entity\Status;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\LazyCriteriaCollection;
use Symfony\Component\HttpFoundation\Request;
use function Symfony\Component\DependencyInjection\Loader\Configurator\expr;

class DishService
{
    private EntityManagerInterface $em;
    private string $DEFAULT_STATUS = 'active';

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    // TODO - extract code to repository impl?
    // TODO - add param validation & sanitization
    // TODO - replace request with just params (service should not depend on request)
    public function findDishes(Request $request): array
    {
        $defaultStatus = $this->em->getRepository(Status::class)->findOneBy(['name' => $this->DEFAULT_STATUS]);

        $qb = $this->em->getRepository('App\Entity\Dish')->createQueryBuilder('o');

        $params = [];
        if ($request->query->get('diff_time') != null) {
            $diffTime = $request->query->get('diff_time');
            $date = new \DateTime();
            $date->setTimestamp($diffTime);
            //$qb->where('tsModified' > :diffTime)
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

        $result = $qb->getQuery()->getResult();

        return $result;
    }

}
