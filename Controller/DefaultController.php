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
use Symfony\Component\HttpFoundation\Response;
use Trinity\Bundle\SearchBundle\Exception\SyntaxErrorException;
use Trinity\Bundle\SearchBundle\NQL\NQLQuery;
use Trinity\Bundle\SearchBundle\Search;
use Trinity\Bundle\SearchBundle\Utils\StringUtils;

/**
 * @Route("/search")
 */
class DefaultController extends FOSRestController
{
    /**
     * @Get("/{tableName}/", name="trinity_table_search")
     *
     * @QueryParam(name="q", nullable=false, strict=true, description="DB Query", allowBlank=true)
     *
     * @param ParamFetcher $paramFetcher
     * @param string $tableName
     *
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     *
     * @throws \Trinity\Bundle\SearchBundle\Exception\SyntaxErrorException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \InvalidArgumentException
     * @throws \Exception
     *
     * @View
     */
    public function tableAction(ParamFetcher $paramFetcher, $tableName)
    {
        $queryParams = $paramFetcher->get('q');

        /** @var Search $search */
        $search = $this->get('trinity.search');

        if ($tableName === 'global') {
            if (StringUtils::isEmpty($queryParams)) {
                throw new \InvalidArgumentException('Query is empty');
            }
            return new Response(
                $search->convertArrayToJson($search->queryGlobal($queryParams)),
                200,
                ['Content-Type' => 'application/json']
            );
        } else {
            try {
                /** @var NQLQuery $nqlQuery */
                $nqlQuery = $search->queryTable($tableName, $queryParams);
                return new Response(
                    $search->convertToJson($nqlQuery, count($nqlQuery->getSelect()->getColumns())),
                    200,
                    ['Content-Type' => 'application/json']
                );

            } catch (SyntaxErrorException $e) {
                $result = $search->queryEntity($tableName, null, null, $queryParams)->getQueryBuilder()->getQuery()->getResult();
                return new Response($search->convertArrayToJson($result));
            }
        }
    }
}
