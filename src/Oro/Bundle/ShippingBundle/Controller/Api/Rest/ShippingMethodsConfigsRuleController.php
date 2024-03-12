<?php

namespace Oro\Bundle\ShippingBundle\Controller\Api\Rest;

use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * REST API Shipping Methods Configs Rule Controller
 */
class ShippingMethodsConfigsRuleController extends RestController
{
    /**
     * Enable shipping rule
     *
     * Returns
     * - HTTP_OK (200)
     *
     * @ApiDoc(description="Enable Shipping Rule", resource=true)
     *
     * @param int $id
     * @return Response
     */
    #[AclAncestor('oro_shipping_methods_configs_rule_update')]
    public function enableAction(int $id)
    {
        /** @var ShippingMethodsConfigsRule $shippingRule */
        $shippingRule = $this->getManager()->find($id);

        if ($shippingRule) {
            $shippingRule->getRule()->setEnabled(true);
            $validateResponse = $this->validateShippingMethodsConfigsRule($shippingRule);
            if ($validateResponse) {
                return $validateResponse;
            }
            $objectManager = $this->getManager()->getObjectManager();
            $objectManager->persist($shippingRule);
            $objectManager->flush();
            $view = $this->view(
                [
                    'message' => $this->container->get(TranslatorInterface::class)
                        ->trans('oro.shipping.notification.channel.enabled'),
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
     * Disable shipping rule
     *
     * Returns
     * - HTTP_OK (200)
     *
     * @ApiDoc(description="Disable Shipping Rule", resource=true)
     *
     * @param int $id
     * @return Response
     */
    #[AclAncestor('oro_shipping_methods_configs_rule_update')]
    public function disableAction(int $id)
    {
        /** @var ShippingMethodsConfigsRule $shippingRule */
        $shippingRule = $this->getManager()->find($id);

        if ($shippingRule) {
            $shippingRule->getRule()->setEnabled(false);
            $validateResponse = $this->validateShippingMethodsConfigsRule($shippingRule);
            if ($validateResponse) {
                return $validateResponse;
            }
            $objectManager = $this->getManager()->getObjectManager();
            $objectManager->persist($shippingRule);
            $objectManager->flush();
            $view = $this->view(
                [
                    'message' => $this->container->get(TranslatorInterface::class)
                        ->trans('oro.shipping.notification.channel.disabled'),
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

    /**
     * @param ShippingMethodsConfigsRule $configsRule
     *
     * @return Response|null
     */
    private function validateShippingMethodsConfigsRule(ShippingMethodsConfigsRule $configsRule)
    {
        $errors = $this->container->get(ValidatorInterface::class)->validate($configsRule);
        if ($errors->count()) {
            $view = $this->view(
                [
                    'message' => $errors->get(0)->getMessage(),
                    'successful' => false,
                ],
                Response::HTTP_BAD_REQUEST
            );

            return $this->handleView(
                $view
            );
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ValidatorInterface::class,
                ApiEntityManager::class,
                TranslatorInterface::class,
            ]
        );
    }
}
