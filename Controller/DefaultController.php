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
use Trinity\Bundle\SearchBundle\Utils\StringUtils;


/**
 * @Route("/search")
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
     * @param string $tableName
     * @return JsonResponse
     * @throws \Exception
     * @View
     */
    public function tableAction(ParamFetcher $paramFetcher, $tableName)
    {
        $queryParams = $paramFetcher->get('q');
        $search = $this->get('trinity.search');

        if ($tableName === "global") {
            if(StringUtils::isEmpty($queryParams)) {
                throw new \Exception("Query is empty");
            }
            return new Response($search->convertArrayToJson($search->queryGlobal($queryParams)), 200, ['Content-Type' => 'application/json']);
        } else {
            $nqlQuery = $search->queryTable($tableName, $queryParams);
            return new Response(
               $search->convertToJson($nqlQuery, count($nqlQuery->getSelect()->getColumns())) , 200, ['Content-Type' => 'application/json']
            );
        }
    }



}
