<?php

namespace OroB2B\Bundle\CheckoutBundle\Entity;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

/**
 * @ORM\Table(name="orob2b_checkout")
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=50)
 */
class Checkout extends ExtendCheckout
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var OrderAddress
     */
    protected $billingAddress;

    /**
     * @var bool
     */
    protected $saveBillingAddress = true;

    /**
     * @var bool
     */
    protected $shipToBillingAddress = false;

    /**
     * @var OrderAddress
     */
    protected $shippingAddress;

    /**
     * @var bool
     */
    protected $saveShippingAddress = true;

    /**
     * @var
     */
    protected $shippingMethod;

    /**
     * @var
     */
    protected $paymentMethod;

    /**
     * @var Order
     */
    protected $order;
}
