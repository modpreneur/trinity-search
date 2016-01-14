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
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializerBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Trinity\Bundle\SearchBundle\NQL\Column;
use Trinity\Bundle\SearchBundle\PassThroughNamingStrategy;
use Trinity\Bundle\SearchBundle\Search;


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

            if(!$skipSelection) {
                return new Response(SerializerBuilder::create()
                    ->setPropertyNamingStrategy(new SerializedNameAnnotationStrategy(new PassThroughNamingStrategy()))
                    ->build()->serialize($entities, 'json'));
            }

            $result = [];

            $select = $nqlQuery->getSelect();

            foreach($entities as $entity) {
                $result[] = $this->select($search, $select->getColumns(), $entity);
            }

            return new Response(SerializerBuilder::create()
                ->setPropertyNamingStrategy(new SerializedNameAnnotationStrategy(new PassThroughNamingStrategy()))
                ->build()->serialize($result, 'json'));
        }
    }

    /**
     * @param  Search   $search
     * @param  Column[] $columns
     * @param  object   $entity
     * @return array
     */
    private function select(Search $search, $columns, $entity) : array {
        $attributes = [];
        foreach($columns as $column) {
            $fullName = $column->getFullName();
            $value = $search->getValue($entity, $fullName);

            $key = count($column->getJoinWith()) ? $column->getJoinWith()[0] : $column->getName();

            if(array_key_exists($key, $attributes)) {
                $attributes[$key] = array_replace_recursive($attributes[$key], $value);
            } else {
                $attributes[$key] = $value;
            }
        }
        return $attributes;
    }
}
