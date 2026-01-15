<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Controller for shopping list REST API requests.
 */
class ShoppingListController extends RestController
{
    /**
     * @ApiDoc(
     *      description="Set current Shopping List",
     *      resource=true
     * )
     * @AclAncestor("oro_shopping_list_frontend_set_as_default")
     *
     * @param ShoppingList $shoppingList
     *
     * @return JsonResponse
     */
    public function setCurrentAction(ShoppingList $shoppingList)
    {
        $user = $this->getUser();
        if (!$user instanceof CustomerUser) {
            throw $this->createAccessDeniedException();
        }

        $this->get('oro_shopping_list.manager.current_shopping_list')
            ->setCurrent($this->getUser(), $shoppingList);

        return $this->buildResponse(
            $this->view([], Response::HTTP_NO_CONTENT),
            self::ACTION_UPDATE,
            ['id' => $shoppingList->getId(), 'success' => $shoppingList->isCurrent()]
        );
    }

    /**
     * @ApiDoc(
     *      description="Set Shopping List Owner",
     *      resource=true
     * )
     * @AclAncestor("oro_shopping_list_frontend_assign")
     *
     * @param Request $request
     * @param ShoppingList $shoppingList
     * @return JsonResponse
     */
    public function setOwnerAction(Request $request, ShoppingList $shoppingList)
    {
        $manager = $this->get('oro_shopping_list.shopping_list.owner_manager');
        $status = Response::HTTP_OK;

        $data = null;
        try {
            $ownerId = $request->request->get("ownerId");
            $manager->setOwner($ownerId, $shoppingList);

            $data = $this->container->get('translator')
                ->trans(
                    'oro.shoppinglist.flash.update_success',
                    ['%shoppinglist%' => $shoppingList->getLabel()]
                );
        } catch (AccessDeniedException $e) {
            $status = Response::HTTP_FORBIDDEN;
        } catch (\InvalidArgumentException $e) {
            $status = Response::HTTP_BAD_REQUEST;
            $data = $e->getMessage();
        }

        return new JsonResponse($data, $status);
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
