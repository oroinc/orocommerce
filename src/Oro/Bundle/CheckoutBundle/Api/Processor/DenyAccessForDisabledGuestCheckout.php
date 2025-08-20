<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies access to guest checkout when this functionality is disabled.
 */
class DenyAccessForDisabledGuestCheckout implements ProcessorInterface
{
    public function __construct(
        private readonly FeatureChecker $featureChecker
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        if (!$this->featureChecker->isFeatureEnabled('guest_checkout')) {
            throw new AccessDeniedException('The guest checkout is disabled.');
        }
    }
}
