<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource\GetSubresourceContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\CheckoutBundle\Api\CheckoutPaymentSubresourceNameProvider;
use Oro\Bundle\CheckoutBundle\Api\Model\CheckoutPaymentResponse;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Validates whether Checkout entity is ready for payment.
 */
class HandleValidatePaymentSubresource implements ProcessorInterface
{
    use CheckoutValidationBeforePaymentSubresourceTrait;

    public function __construct(
        private readonly ActionProcessorBagInterface $processorBag,
        private readonly CheckoutPaymentSubresourceNameProvider $checkoutPaymentSubresourceNameProvider,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RestRoutesRegistry $routesRegistry,
        private readonly ValueNormalizer $valueNormalizer
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var GetSubresourceContext $context */

        $updateProcessor = $this->processorBag->getProcessor(ApiAction::UPDATE);
        $updateContext = $this->createUpdateContext($context, $updateProcessor);
        $updateProcessor->process($updateContext);
        if ($updateContext->hasErrors()) {
            $errors = $updateContext->getErrors();
            foreach ($errors as $error) {
                $context->addError($error);
            }
            $context->setConfig($updateContext->getConfig());
            $context->setMetadata($updateContext->getMetadata());
        } else {
            $paymentUrl = null;
            /** @var Checkout $checkout */
            $checkout = $updateContext->getResult();
            $subresourceName = $this->getCheckoutPaymentSubresourceName($checkout->getPaymentMethod());
            if ($subresourceName) {
                $paymentUrl = $this->urlGenerator->generate(
                    $this->routesRegistry->getRoutes($context->getRequestType())->getSubresourceRouteName(),
                    [
                        'entity' => ValueNormalizerUtil::convertToEntityType(
                            $this->valueNormalizer,
                            $context->getParentClassName(),
                            $context->getRequestType()
                        ),
                        'id' => $context->getParentId(),
                        'association' => $subresourceName
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            }
            $context->setResult(new CheckoutPaymentResponse('The checkout is ready for payment.', $paymentUrl));
        }
    }

    private function getCheckoutPaymentSubresourceName(?string $paymentMethod): ?string
    {
        if (!$paymentMethod) {
            return null;
        }

        return $this->checkoutPaymentSubresourceNameProvider->getCheckoutPaymentSubresourceName($paymentMethod);
    }
}
