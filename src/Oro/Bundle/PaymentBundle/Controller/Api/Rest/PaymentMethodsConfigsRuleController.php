<?php

namespace Oro\Bundle\PaymentBundle\Controller\Api\Rest;

use Doctrine\Persistence\ObjectManager;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API Payment Methods Configs Rule Controller
 */
class PaymentMethodsConfigsRuleController extends RestController
{
    /**
     * Enable payment rule
     *
     * Returns
     * - HTTP_OK (200)
     *
     * @ApiDoc(description="Enable Payment Rule", resource=true)
     * @AclAncestor("oro_payment_methods_configs_rule_update")
     *
     * @param $id
     * @return Response
     */
    public function enableAction(int $id)
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
                Response::HTTP_OK
            );
        } else {
            /** @var View $view */
            $view = $this->view(null, Response::HTTP_NOT_FOUND);
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
     * @ApiDoc(description="Disable Payment Rule", resource=true)
     * @AclAncestor("oro_payment_methods_configs_rule_update")
     *
     * @param $id
     * @return Response
     */
    public function disableAction(int $id)
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
                Response::HTTP_OK
            );
        } else {
            /** @var View $view */
            $view = $this->view(null, Response::HTTP_NOT_FOUND);
        }

        return $this->handleView(
            $view
        );
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
