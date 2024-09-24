<?php

namespace Oro\Bundle\PaymentBundle\Controller\Api\Rest;

use Doctrine\Persistence\ObjectManager;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
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
     *
     * @param int $id
     * @return Response
     */
    #[AclAncestor('oro_payment_methods_configs_rule_update')]
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
                        $this->container->get('translator')
                            ->trans('oro.payment.paymentmethodsconfigsrule.notification.enabled'),
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
     *
     * @param int $id
     * @return Response
     */
    #[AclAncestor('oro_payment_methods_configs_rule_update')]
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
                        $this->container->get('translator')
                            ->trans('oro.payment.paymentmethodsconfigsrule.notification.disabled'),
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

    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_payment.payment_methods_configs_rule.manager.api');
    }

    #[\Override]
    public function getForm()
    {
        throw new \LogicException('This method should not be called');
    }

    #[\Override]
    public function getFormHandler()
    {
        throw new \LogicException('This method should not be called');
    }
}
