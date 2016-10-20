<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend\Api\Rest;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @RouteResource("shoppinglist")
 * @NamePrefix("oro_api_")
 */
class ShoppingListController extends RestController implements ClassResourceInterface
{
    /**
     * @Put("/shoppinglists/current/{id}")
     *
     * @ApiDoc(
     *      description="Set current Shopping List",
     *      resource=true
     * )
     * @AclAncestor("oro_shopping_list_frontend_update")
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function setCurrentAction($id)
    {
        /** @var ShoppingListManager $manager */
        $manager = $this->get('oro_shopping_list.shopping_list.manager');

        $shoppingList = $this->get('oro_shopping_list.repository.shopping_list')->find($id);

        if ($shoppingList === null) {
            throw $this->createNotFoundException('Can\'t find shopping list with id ' . $id);
        }

        $manager->setCurrent($this->getUser(), $shoppingList);

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @ApiDoc(
     *      description="Delete Shopping List",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_shopping_list_frontend_delete",
     *      type="entity",
     *      class="OroShoppingListBundle:ShoppingList",
     *      permission="DELETE",
     *      group_name="commerce"
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
        return $this->get('oro_shopping_list.shopping_list.manager.api');
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
