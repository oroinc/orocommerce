<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\CheckoutBundle\Api\GuestCheckoutChecker;
use Oro\Bundle\CustomerBundle\Validator\Constraints\FrontendOwner;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes FrontendOwner validation constraint
 * when the current security context represents a visitor
 * and the checkout feature is enabled for visitors.
 */
class ConfigureGuestCheckoutOwnerAccess implements ProcessorInterface
{
    private GuestCheckoutChecker $guestCheckoutChecker;
    private OwnershipMetadataProviderInterface $ownershipMetadataProvider;

    public function __construct(
        GuestCheckoutChecker $guestCheckoutChecker,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider
    ) {
        $this->guestCheckoutChecker = $guestCheckoutChecker;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        if (!$this->guestCheckoutChecker->isGuestWithEnabledCheckout()) {
            return;
        }

        $ownerFieldName = $this->ownershipMetadataProvider
            ->getMetadata($context->getClassName())
            ->getOwnerFieldName();
        if (!$ownerFieldName) {
            return;
        }

        $config = $context->getResult();
        $field = $config->findField($ownerFieldName, true);
        if (null === $field || $field->isExcluded()) {
            return;
        }

        $config->removeFormConstraint(FrontendOwner::class);
    }
}
