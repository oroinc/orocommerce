<?php

namespace Oro\Bundle\ShippingBundle\Controller\Api\Rest;

use Doctrine\Common\Persistence\ObjectManager;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\View;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;

/**
 * @RouteResource("shippingrules")
 * @NamePrefix("orob2b_api_")
 */
class ShippingRuleController extends RestController implements ClassResourceInterface
{
    /**
     * Enable shipping rule
     *
     * Returns
     * - HTTP_OK (200)
     *
     * @Get(
     *      "/shippingrules/{id}/enable",
     *      requirements={"version"="latest|v1"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(description="Enable Shipping Rule", resource=true)
     * @AclAncestor("orob2b_shipping_rule_update")
     *
     * @return Response
     */
    public function enableAction($id)
    {
        /** @var ShippingRule $shippingRule */
        $shippingRule = $this->getManager()->find($id);

        if ($shippingRule) {
            $shippingRule->setEnabled(true);
            /** @var ObjectManager $objectManager */
            $objectManager = $this->getManager()->getObjectManager();
            $objectManager->persist($shippingRule);
            $objectManager->flush();
            $view = $this->view(
                [
                    'message'    => $this->get('translator')->trans('oro.shipping.notification.channel.enabled'),
                    'successful' => true,
                ],
                Codes::HTTP_OK
            );
        } else {
            /** @var View $view */
            $view = $this->view(null, Codes::HTTP_NOT_FOUND);
        }


        return $this->handleView(
            $view
        );
    }

    /**
     * Disable shipping rule
     *
     * Returns
     * - HTTP_OK (200)
     *
     * @Get(
     *      "/shippingrules/{id}/disable",
     *      requirements={"version"="latest|v1"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(description="Disable Shipping Rule", resource=true)
     * @AclAncestor("orob2b_shipping_rule_update")
     *
     * @return Response
     */
    public function disableAction($id)
    {
        /** @var ShippingRule $shippingRule */
        $shippingRule = $this->getManager()->find($id);

        if ($shippingRule) {
            $shippingRule->setEnabled(false);
            /** @var ObjectManager $objectManager */
            $objectManager = $this->getManager()->getObjectManager();
            $objectManager->persist($shippingRule);
            $objectManager->flush();
            $view = $this->view(
                [
                    'message'    => $this->get('translator')->trans('oro.shipping.notification.channel.disabled'),
                    'successful' => true,
                ],
                Codes::HTTP_OK
            );
        } else {
            /** @var View $view */
            $view = $this->view(null, Codes::HTTP_NOT_FOUND);
        }


        return $this->handleView(
            $view
        );
    }

    /**
     * Rest delete
     *
     * @ApiDoc(
     *      description="Delete Shipping Rule",
     *      resource=true
     * )
     * @Acl(
     *      id="orob2b_shipping_rule_delete",
     *      type="entity",
     *      permission="DELETE",
     *      class="OroShippingBundle:ShippingRule"
     * )
     *
     * @param int $id
     * @return Response
     *
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
        return $this->get('oro_shipping.shipping_rule.manager.api');
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
