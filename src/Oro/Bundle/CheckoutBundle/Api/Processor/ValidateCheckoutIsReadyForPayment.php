<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorMetaProperty;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Validates whether Checkout entity is ready to be processed by a payment sub-resource.
 */
class ValidateCheckoutIsReadyForPayment implements ProcessorInterface
{
    use CheckoutValidationBeforePaymentSubresourceTrait;

    public function __construct(
        private readonly ActionProcessorBagInterface $processorBag,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RestRoutesRegistry $routesRegistry,
        private readonly ValueNormalizer $valueNormalizer
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ChangeSubresourceContext $context */

        $updateProcessor = $this->processorBag->getProcessor(ApiAction::UPDATE);
        $updateContext = $this->createUpdateContext($context, $updateProcessor);
        $updateContext->setResult($context->getParentEntity());
        $updateProcessor->process($updateContext);
        if ($updateContext->hasErrors()) {
            $error = Error::createValidationError(
                'payment constraint',
                'The checkout is not ready for payment.'
            );
            $error->addMetaProperty('validatePaymentUrl', new ErrorMetaProperty(
                $this->urlGenerator->generate(
                    $this->routesRegistry->getRoutes($context->getRequestType())->getSubresourceRouteName(),
                    [
                        'entity' => ValueNormalizerUtil::convertToEntityType(
                            $this->valueNormalizer,
                            $context->getParentClassName(),
                            $context->getRequestType()
                        ),
                        'id' => $context->getParentId(),
                        'association' => 'payment'
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ));
            $context->addError($error);
        }
    }
}
