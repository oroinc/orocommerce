<?php

namespace OroB2B\Bundle\ShoppingListBundle\Controller\Frontend\Api\Rest;

use FOS\RestBundle\Util\Codes;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler;
use OroB2B\Bundle\ProductBundle\Form\Type\FrontendLineItemType;

/**
 * @NamePrefix("orob2b_api_shopping_list_frontend_")
 */
class LineItemController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Delete Line Item",
     *      resource=true
     * )
     * @Acl(
     *      id="orob2b_shopping_list_line_item_frontend_delete",
     *      type="entity",
     *      class="OroB2BShoppingListBundle:LineItem",
     *      permission="DELETE",
     *      group_name="commerce"
     * )
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $success = false;
        /** @var LineItem $lineItem */
        $lineItem = $this->getDoctrine()
            ->getManagerForClass('OroB2BShoppingListBundle:LineItem')
            ->getRepository('OroB2BShoppingListBundle:LineItem')
            ->find($id);

        $view = $this->view(null, Codes::HTTP_NO_CONTENT);
        if ($lineItem) {
            $this->get('orob2b_shopping_list.shopping_list.manager')->removeLineItem($lineItem);
            $success = true;
        }

        return $this->buildResponse($view, self::ACTION_DELETE, ['id' => $lineItem->getId(), 'success' => $success]);
    }

    /**
     * @ApiDoc(
     *      description="Update Line Item",
     *      resource=true
     * )
     * @AclAncestor("orob2b_shopping_list_frontend_update")
     *
     * @param int $id
     *
     * @param Request $request
     * @return Response
     */
    public function putAction($id, Request $request)
    {
        /** @var LineItem $entity */
        $entity = $this->getManager()->find($id);

        if ($entity) {
            $form = $this->createForm(FrontendLineItemType::NAME, $entity, ['csrf_protection' => false]);

            $handler = new LineItemHandler(
                $form,
                $request,
                $this->getDoctrine(),
                $this->get('orob2b_shopping_list.shopping_list.manager')
            );
            $isFormHandled = $handler->process($entity);
            if ($isFormHandled) {
                $view = $this->view(
                    ['unit' => $entity->getUnit()->getCode(), 'quantity' => $entity->getQuantity()],
                    Codes::HTTP_OK
                );
            } else {
                $view = $this->view($form, Codes::HTTP_BAD_REQUEST);
            }
        } else {
            $view = $this->view(null, Codes::HTTP_NOT_FOUND);
        }

        return $this->buildResponse($view, self::ACTION_UPDATE, ['id' => $id, 'entity' => $entity]);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('orob2b_shopping_list.line_item.manager.api');
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
