<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * Prepare checkout settings by a given source.
 */
interface PrepareCheckoutSettingsInterface
{
    /**
     * @param CheckoutSourceEntityInterface $source
     * @return array{
     *     billing_address?: \Oro\Bundle\OrderBundle\Entity\OrderAddress,
     *     shipping_address?: \Oro\Bundle\OrderBundle\Entity\OrderAddress,
     *     shipping_method?: string|null,
     *     shipping_method_type?: string,
     *     payment_method?: string
     * }
     */
    public function execute(CheckoutSourceEntityInterface $source): array;
}
