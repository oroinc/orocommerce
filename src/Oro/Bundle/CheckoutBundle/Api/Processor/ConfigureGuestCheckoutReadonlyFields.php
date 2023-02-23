<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\CheckoutBundle\Api\GuestCheckoutChecker;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Marks specified fields as read-only by disabling form mapping for them
 * when the current security context represents a visitor
 * and the checkout feature is enabled for visitors.
 */
class ConfigureGuestCheckoutReadonlyFields implements ProcessorInterface
{
    private GuestCheckoutChecker $guestCheckoutChecker;
    /** @var string[] */
    private array $fieldNames;

    public function __construct(
        GuestCheckoutChecker $guestCheckoutChecker,
        array $fieldNames
    ) {
        $this->guestCheckoutChecker = $guestCheckoutChecker;
        $this->fieldNames = $fieldNames;
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

        $config = $context->getResult();
        foreach ($this->fieldNames as $fieldName) {
            $config->findField($fieldName, true)?->setFormOption('mapped', false);
        }
    }
}
