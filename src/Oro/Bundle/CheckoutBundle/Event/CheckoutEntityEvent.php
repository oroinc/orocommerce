<?php

namespace Oro\Bundle\CheckoutBundle\Event;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * CheckoutEntityEvent represents logic which was performed on checkout
 */
class CheckoutEntityEvent extends Event
{
    protected ?CheckoutInterface $checkoutEntity = null;

    protected ?CheckoutSource $source = null;

    protected ?int $checkoutId = null;

    public function getCheckoutEntity(): ?CheckoutInterface
    {
        return $this->checkoutEntity;
    }

    public function setCheckoutEntity(?CheckoutInterface $checkoutEntity): self
    {
        $this->checkoutEntity = $checkoutEntity;
        return $this;
    }

    public function getSource(): ?CheckoutSource
    {
        return $this->source;
    }

    public function setSource(?CheckoutSource $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getCheckoutId(): ?int
    {
        return $this->checkoutId;
    }

    public function setCheckoutId(?int $checkoutId): self
    {
        $this->checkoutId = $checkoutId;
        return $this;
    }
}
