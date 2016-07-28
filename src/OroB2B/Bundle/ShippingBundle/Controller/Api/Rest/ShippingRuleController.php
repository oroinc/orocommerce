<?php

namespace OroB2B\Bundle\ShippingBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;

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
     * @Acl(
     *      id="orob2b_shipping_rule_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroB2BShippingBundle:ShippingRule"
     * )
     *
     * @return Response
     */
    public function enableAction($id)
    {
        /** @var ShippingRule $shippingRule */
        $shippingRule = $this->getManager()->find($id);

        if ($shippingRule) {
            $shippingRule->setEnabled(true);
            $objectManager = $this->getManager()->getObjectManager();
            $objectManager->persist($shippingRule);
            $objectManager->flush();
            $view = $this->view(
                [
                    'message'    => $this->get('translator')->trans('orob2b.shipping.notification.channel.enabled'),
                    'successful' => true,
                ],
                Codes::HTTP_OK
            );
        } else {
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
     * @Acl(
     *      id="orob2b_shipping_rule_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroB2BShippingBundle:ShippingRule"
     * )
     *
     * @return Response
     */
    public function disableAction($id)
    {
        /** @var ShippingRule $shippingRule */
        $shippingRule = $this->getManager()->find($id);

        if ($shippingRule) {
            $shippingRule->setEnabled(false);
            $objectManager = $this->getManager()->getObjectManager();
            $objectManager->persist($shippingRule);
            $objectManager->flush();
            $view = $this->view(
                [
                    'message'    => $this->get('translator')->trans('orob2b.shipping.notification.channel.disabled'),
                    'successful' => true,
                ],
                Codes::HTTP_OK
            );
        } else {
            $view = $this->view(null, Codes::HTTP_NOT_FOUND);
        }


        return $this->handleView(
            $view
        );
    }

    /**
     * REST DELETE
     *
     * @param int $id
     *
     * @ApiDoc(
     *      description="Delete Shipping Rule",
     *      resource=true
     * )
     * @Acl(
     *      id="orob2b_shipping_rule_delete",
     *      type="entity",
     *      permission="DELETE",
     *      class="OroB2BShippingBundle:ShippingRule"
     * )
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
        return $this->get('orob2b_shipping.shipping_rule.manager.api');
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
