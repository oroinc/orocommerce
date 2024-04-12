<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * Prepare checkout settings by a given source.
 */
interface PrepareCheckoutSettingsInterface
{
    public function execute(CheckoutSourceEntityInterface $source): array;
}
