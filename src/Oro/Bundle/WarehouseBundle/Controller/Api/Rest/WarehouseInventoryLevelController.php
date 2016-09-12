<?php

namespace Oro\Bundle\WarehouseBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @RouteResource("warehouse_inventory_level")
 * @NamePrefix("oro_api_warehouse_")
 */
class WarehouseInventoryLevelController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Delete warehouse inventory level",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_warehouse_inventory_level_delete",
     *      type="entity",
     *      class="OroWarehouseBundle:WarehouseInventoryLevel",
     *      permission="DELETE"
     * )
     *
     * @param int $id
     * @return Response
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_warehouse.warehouse_inventory_level.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \LogicException('This method should not be called');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \LogicException('This method should not be called');
    }
}
