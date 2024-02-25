<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend\Api\Rest;

use Doctrine\Persistence\ManagerRegistry;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Handler\DelegateIncludeHandler;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Controller for shopping list line item REST API requests.
 */
class LineItemController extends RestController
{
    /**
     * @ApiDoc(
     *      description="Delete Line Item",
     *      resource=true
     * )
     *
     * @param int $id
     * @param int $onlyCurrent
     * @return Response
     */
    #[AclAncestor('oro_shopping_list_frontend_update')]
    public function deleteAction(int $id, int $onlyCurrent = 0)
    {
        $success = false;
        /** @var LineItem $lineItem */
        $lineItem = $this->container->get('doctrine')
            ->getManagerForClass(LineItem::class)
            ->getRepository(LineItem::class)
            ->find($id);

        $view = $this->view(null, Response::HTTP_NO_CONTENT);

        if ($lineItem) {
            if ($this->isGranted('DELETE', $lineItem) && $this->isGranted('EDIT', $lineItem->getShoppingList())) {
                $this->container->get(ShoppingListManager::class)->removeLineItem(
                    $lineItem,
                    (bool) $onlyCurrent
                );
                $success = true;
            } else {
                $view = $this->view(null, Response::HTTP_FORBIDDEN);
            }
        } else {
            $view = $this->view(null, Response::HTTP_NOT_FOUND);
        }

        return $this->buildResponse($view, self::ACTION_DELETE, ['id' => $id, 'success' => $success]);
    }

    /**
     * @ApiDoc(
     *      description="Delete Line Item",
     *      resource=true
     * )
     */
    #[AclAncestor('oro_shopping_list_frontend_update')]
    public function deleteConfigurableAction(int $shoppingListId, int $productId, string $unitCode): Response
    {
        $success = false;

        /** @var LineItem[] $lineItems */
        $lineItems = $this->container->get('doctrine')
            ->getManagerForClass(LineItem::class)
            ->getRepository(LineItem::class)
            ->findLineItemsByParentProductAndUnit($shoppingListId, $productId, $unitCode);

        $view = $this->view(null, Response::HTTP_NO_CONTENT);

        $allowed = false;
        $ids = [];

        if ($lineItems) {
            foreach ($lineItems as $lineItem) {
                if (!$this->isGranted('DELETE', $lineItem) || !$this->isGranted('EDIT', $lineItem->getShoppingList())) {
                    break;
                }

                $allowed = true;
            }

            if ($allowed) {
                $options = [];
                $handler = $this->container->get('oro_entity.delete_handler_registry')
                    ->getHandler(LineItem::class);

                foreach ($lineItems as $lineItem) {
                    $handler->delete($lineItem, false);

                    $options[]['entity'] = $lineItem;
                    $ids[] = $lineItem->getId();
                }

                $handler->flushAll($options);

                $success = true;
            } else {
                $view = $this->view(null, Response::HTTP_FORBIDDEN);
            }
        } else {
            $view = $this->view(null, Response::HTTP_NOT_FOUND);
        }

        return $this->buildResponse($view, self::ACTION_DELETE, ['ids' => $ids, 'success' => $success]);
    }

    /**
     * @ApiDoc(
     *      description="Update Line Item",
     *      resource=true
     * )
     *
     * @param int $id
     *
     * @param Request $request
     * @return Response
     */
    #[AclAncestor('oro_shopping_list_frontend_update')]
    public function putAction(int $id, Request $request)
    {
        /** @var LineItem $entity */
        $entity = $this->getManager()->find($id);

        if ($entity) {
            if ($this->isGranted('EDIT', $entity) && $this->isGranted('EDIT', $entity->getShoppingList())) {
                $form = $this->createForm(FrontendLineItemType::class, $entity, ['csrf_protection' => false]);

                $handler = new LineItemHandler(
                    $form,
                    $request,
                    $this->container->get('doctrine'),
                    $this->container->get(ShoppingListManager::class),
                    $this->container->get(CurrentShoppingListManager::class),
                    $this->container->get('validator')
                );
                $isFormHandled = $handler->process($entity);
                if ($isFormHandled) {
                    $view = $this->view(
                        ['unit' => $entity->getUnit()->getCode(), 'quantity' => $entity->getQuantity()],
                        Response::HTTP_OK
                    );
                } else {
                    $view = $this->view($form, Response::HTTP_BAD_REQUEST);
                }
            } else {
                $view = $this->view(null, Response::HTTP_FORBIDDEN);
            }
        } else {
            $view = $this->view(null, Response::HTTP_NOT_FOUND);
        }

        return $this->buildResponse($view, self::ACTION_UPDATE, ['id' => $id, 'entity' => $entity]);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->container->get(ApiEntityManager::class);
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

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ApiEntityManager::class,
                ShoppingListManager::class,
                CurrentShoppingListManager::class,
                'doctrine' => ManagerRegistry::class,
                'validator' => ValidatorInterface::class,
                'form.factory' => FormFactoryInterface::class,
                'oro_soap.handler.include' => DelegateIncludeHandler::class,
                'oro_entity.delete_handler_registry' => EntityDeleteHandlerRegistry::class,
            ]
        );
    }
}
