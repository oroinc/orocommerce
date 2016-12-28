<?php

namespace Oro\Bundle\PaymentBundle\Context\LineItem\Collection;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;

interface PaymentLineItemCollectionInterface extends Collection
{
    /**
     * @return PaymentLineItemInterface
     */
    public function current();

    /**
     * @param int|string $key
     *
     * @return PaymentLineItemInterface
     */
    public function get($key);

    /**
     * @return PaymentLineItemInterface
     */
    public function first();

    /**
     * @return PaymentLineItemInterface
     */
    public function last();

    /**
     * @return PaymentLineItemInterface
     */
    public function next();

    /**
     * @param int|string $key
     *
     * @return PaymentLineItemInterface
     */
    public function remove($key);
}
