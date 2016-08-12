<?php

namespace OroB2B\Bundle\CheckoutBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;

class CheckoutEntityEvent extends Event
{
    /**
     * @var CheckoutInterface
     */
    protected $checkoutEntity;

    /**
     * @var CheckoutSource
     */
    protected $source;

    /**
     * @var int
     */
    protected $checkoutId;

    /**
     * @return CheckoutInterface
     */
    public function getCheckoutEntity()
    {
        return $this->checkoutEntity;
    }

    /**
     * @param CheckoutInterface $checkoutEntity
     */
    public function setCheckoutEntity(CheckoutInterface $checkoutEntity = null)
    {
        $this->checkoutEntity = $checkoutEntity;
    }

    /**
     * @return CheckoutSource
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param CheckoutSource $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return int
     */
    public function getCheckoutId()
    {
        return $this->checkoutId;
    }

    /**
     * @param int $checkoutId
     * @return $this
     */
    public function setCheckoutId($checkoutId)
    {
        $this->checkoutId = $checkoutId;
        return $this;
    }
}
