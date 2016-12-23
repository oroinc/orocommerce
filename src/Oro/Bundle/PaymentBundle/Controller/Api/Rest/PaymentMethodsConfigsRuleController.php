<?php

namespace Oro\Bundle\PaymentBundle\Controller\Api\Rest;

use Doctrine\Common\Persistence\ObjectManager;

use FOS\RestBundle\Controller\Annotations\Get;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("paymentmethodsconfigsrules")
 * @NamePrefix("oro_api_")
 */
class PaymentMethodsConfigsRuleController extends RestController implements ClassResourceInterface
{
    /**
     * Enable payment rule
     *
     * Returns
     * - HTTP_OK (200)
     *
     * @Get(
     *      "/paymentrules/{id}/enable",
     *      requirements={"version"="latest|v1"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(description="Enable Payment Rule", resource=true)
     * @AclAncestor("oro_payment_methods_configs_rule_update")
     *
     * @param $id
     * @return Response
     */
    public function enableAction($id)
    {
        /** @var PaymentMethodsConfigsRule $paymentMethodsConfigsRule */
        $paymentMethodsConfigsRule = $this->getManager()->find($id);

        if ($paymentMethodsConfigsRule) {
            $paymentMethodsConfigsRule->getRule()->setEnabled(true);
            /** @var ObjectManager $objectManager */
            $objectManager = $this->getManager()->getObjectManager();
            $objectManager->persist($paymentMethodsConfigsRule);
            $objectManager->flush();
            $view = $this->view(
                [
                    'message'    =>
                        $this->get('translator')->trans('oro.payment.paymentmethodsconfigsrule.notification.enabled'),
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
     * Disable payment rule
     *
     * Returns
     * - HTTP_OK (200)
     *
     * @Get(
     *      "/paymentrules/{id}/disable",
     *      requirements={"version"="latest|v1"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(description="Disable Payment Rule", resource=true)
     * @AclAncestor("oro_payment_methods_configs_rule_update")
     *
     * @param $id
     * @return Response
     */
    public function disableAction($id)
    {
        /** @var PaymentMethodsConfigsRule $paymentMethodsConfigsRule */
        $paymentMethodsConfigsRule = $this->getManager()->find($id);

        if ($paymentMethodsConfigsRule) {
            $paymentMethodsConfigsRule->getRule()->setEnabled(false);
            /** @var ObjectManager $objectManager */
            $objectManager = $this->getManager()->getObjectManager();
            $objectManager->persist($paymentMethodsConfigsRule);
            $objectManager->flush();
            $view = $this->view(
                [
                    'message'    =>
                        $this->get('translator')->trans('oro.payment.paymentmethodsconfigsrule.notification.disabled'),
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
     *      description="Delete Payment Rule",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_payment_methods_configs_rule_delete",
     *      type="entity",
     *      permission="DELETE",
     *      class="OroPaymentBundle:PaymentMethodsConfigsRule"
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
        return $this->get('oro_payment.payment_methods_configs_rule.manager.api');
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
