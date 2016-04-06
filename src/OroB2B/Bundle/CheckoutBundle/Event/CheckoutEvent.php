<?php

namespace OroB2B\Bundle\CheckoutBundle\Event;

use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Symfony\Component\EventDispatcher\Event;

class CheckoutEvent extends Event
{
    /**
     * @var CheckoutInterface
     */
    protected $checkoutEntity;

    /**
     * @var object
     */
    protected $source;

    /**
     * @var int
     */
    protected $checkoutId;

    /**
     * @var string
     */
    protected $type;

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
    public function setCheckoutEntity(CheckoutInterface $checkoutEntity)
    {
        $this->checkoutEntity = $checkoutEntity;
    }

    /**
     * @return object
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param object $source
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

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
}
