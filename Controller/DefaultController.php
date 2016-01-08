<?php

namespace Trinity\SearchBundle\Controller;

use Doctrine\ORM\Query;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
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
     * @ApiDoc()
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
            return $search->queryTable($tableName, $queryParams);
        }
    }
}
