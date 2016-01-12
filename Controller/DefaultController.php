<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\Controller;

use Doctrine\ORM\Query;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * @Route("/admin/search")
 */
class DefaultController extends FOSRestController
{
    /**
     * @Get("/{tableName}/")
     *
     * @QueryParam(name="q", nullable=false, strict=true, description="DB Query", allowBlank=true)
     *
     * @param ParamFetcher $paramFetcher
     *
     * @return JsonResponse
     *
     * @View
     */
    public function tableAction(ParamFetcher $paramFetcher, $tableName)
    {
        $queryParams = $paramFetcher->get('q');
        $search = $this->get('trinity.search');

        if ($tableName === "global") {
            return $search->queryGlobal($queryParams);
        }
        else {
            $nqlQuery = $search->queryTable($tableName, $queryParams);

            $skipSelection = count($nqlQuery->getSelect()->getColumns());

            $entities = $nqlQuery->getQueryBuilder($skipSelection)->getQuery()->getResult();

            if(!$skipSelection)
                return $entities;
            
            $result = [];

            $select = $nqlQuery->getSelect();

            foreach($entities as $entity) {
                $result[] = $this->select($search, $select->getColumns(), $entity);
            }

            return $result;
        }
    }

    private function select($search, $columns, $entity) {
        $attributes = [];
        foreach($columns as $column) {
            $fullName = $column->getFullName();
            $attributes[$fullName] = $search->getValue($entity, $fullName);
        }
        return $attributes;
    }
}
