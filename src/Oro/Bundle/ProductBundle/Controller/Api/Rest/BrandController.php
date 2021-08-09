<?php

namespace Oro\Bundle\ProductBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;

/**
 * REST API CRUD controller for Brand entity
 */
class BrandController extends RestController
{
    /**
     * @param int $id Brand id
     *
     * @ApiDoc(
     *     description="Get sissue",
     *     resource=true
     * )
     * @AclAncestor("oro_product_brand_view")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction(int $id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * Delete brand
     *
     * @param int $id Brand id
     *
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
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(int $id)
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
