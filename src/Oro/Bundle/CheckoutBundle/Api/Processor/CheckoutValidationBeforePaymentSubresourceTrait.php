<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\Extension\ValidationExtension;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

/**
 * Provides functionality that helps to validate whether Checkout entity is ready for payment.
 */
trait CheckoutValidationBeforePaymentSubresourceTrait
{
    private function createUpdateContext(
        SubresourceContext $context,
        ActionProcessorInterface $processor
    ): UpdateContext {
        /** @var UpdateContext $updateContext */
        $updateContext = $processor->createContext();
        $updateContext->setVersion($context->getVersion());
        $updateContext->getRequestType()->set($context->getRequestType());
        $updateContext->setRequestHeaders($context->getRequestHeaders());
        $updateContext->setSharedData($context->getSharedData());
        $updateContext->setHateoas($context->isHateoasEnabled());
        $updateContext->setClassName($context->getParentClassName());
        $updateContext->setId($context->getParentId());
        $updateContext->skipGroup(ApiActionGroup::RESOURCE_CHECK);
        $updateContext->skipGroup(ApiActionGroup::NORMALIZE_INPUT);
        $updateContext->skipGroup(ApiActionGroup::SECURITY_CHECK);
        $updateContext->skipGroup(ApiActionGroup::DATA_SECURITY_CHECK);
        $updateContext->skipGroup(ApiActionGroup::SAVE_DATA);
        $updateContext->skipGroup(ApiActionGroup::NORMALIZE_DATA);
        $updateContext->skipGroup(ApiActionGroup::NORMALIZE_RESULT);
        $updateContext->setSoftErrorsHandling(true);
        $updateContext->setRequestData([]);
        $updateContext->setFormOptions([
            ValidationExtension::ENABLE_FULL_VALIDATION => true,
            'validation_groups' => ['checkout_order_create_pre_checks', 'checkout_order_create_checks']
        ]);

        return $updateContext;
    }
}
