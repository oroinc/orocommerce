<?php

namespace Oro\Bundle\ProductBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;

/**
 * @RouteResource("brand")
 * @NamePrefix("oro_api_")
 */
class BrandController extends RestController implements ClassResourceInterface
{
    /**
     * @param int $id Brand id
     * @ApiDoc(
     *     description="Get sissue",
     *     resource=true
     * )
     * @Acl(
     *      id="oro_product_brand_view",
     *      type="entity",
     *      class="OroProductBundle:Brand",
     *      permission="VIEW"
     * )
     * @Get(requirements={"id"="\d+"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * Delete brand
     *
     * @param int $id Brand id
     * @ApiDoc(
     *      description="Delete brand",
     *      resource=true,
     *      requirements={
     *          {"name"="id", "dataType"="integer"},
     *      }
     * )
     * @Acl(
     *      id="oro_product_brand_delete",
     *      type="entity",
     *      class="OroProductBundle:Brand",
     *      permission="DELETE"
     * )
     * @Delete(requirements={"id"="\d+"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * Get entity Manager
     *
     * @return ApiEntityManager
     */
    public function getManager()
    {
        return $this->get('oro_product.brand.manager.api');
    }

    /**
     * @return ApiFormHandler
     */
    public function getForm()
    {
        throw new \BadMethodCallException('Form is not available.');
    }

    /**
     * @return ApiFormHandler
     */
    public function getFormHandler()
    {
        throw new \BadMethodCallException('Form handler is not available.');
    }
}
