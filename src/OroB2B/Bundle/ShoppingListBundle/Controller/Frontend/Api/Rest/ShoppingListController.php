<?php

namespace OroB2B\Bundle\ShoppingListBundle\Controller\Frontend\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @RouteResource("shoppinglist")
 * @NamePrefix("orob2b_api_")
 */
class ShoppingListController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Delete Shopping List",
     *      resource=true
     * )
     * @Acl(
     *      id="orob2b_shopping_list_frontend_delete",
     *      type="entity",
     *      class="OroB2BShoppingListBundle:ShoppingList",
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
        return $this->get('orob2b_shopping_list.shopping_list.manager.api');
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
