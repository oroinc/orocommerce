<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;

/**
 * Entity holds checkout subtotals data by currency
 * If isValid=false values should be recalculated
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutSubtotalRepository")
 * @ORM\Table(
 *     name="oro_checkout_subtotal",
 *     indexes={
 *         @ORM\Index(name="idx_checkout_subtotal_valid", columns={"is_valid"})
 *     },
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(name="unique_checkout_currency", columns={
 *              "checkout_id",
 *              "currency"
 *          })
 *      }
 * )
 **/
class CheckoutSubtotal
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Checkout
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CheckoutBundle\Entity\Checkout", inversedBy="subtotals")
     * @ORM\JoinColumn(name="checkout_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $checkout;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=255, nullable=false)
     */
    protected $currency;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="money", nullable=true)
     */
    protected $value;

    /**
     * @var CombinedPriceList
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PricingBundle\Entity\CombinedPriceList")
     * @ORM\JoinColumn(name="combined_price_list_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    protected $combinedPriceList;

    /**
     * @var PriceList
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PricingBundle\Entity\PriceList")
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    protected $priceList;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_valid", type="boolean")
     */
    protected $valid = false;

    /**
     * @param Checkout $checkout
     * @param string $currency
     */
    public function __construct(Checkout $checkout, $currency)
    {
        $this->checkout = $checkout;
        $this->currency = $currency;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Checkout
     */
    public function getCheckout()
    {
        return $this->checkout;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * @param boolean $valid
     * @return $this
     */
    public function setValid($valid)
    {
        $this->valid = $valid;

        return $this;
    }

    /**
     * @param Subtotal $subtotal
     * @return $this
     */
    public function setSubtotal(Subtotal $subtotal)
    {
        if ($subtotal->getCurrency() !== $this->currency) {
            throw new \InvalidArgumentException('Invalid currency for Checkout Subtotal');
        }

        $this->value = $subtotal->getAmount();
        if ($subtotal->getPriceList() instanceof CombinedPriceList) {
            $this->combinedPriceList = $subtotal->getPriceList();
        }
        if ($subtotal->getPriceList() instanceof PriceList) {
            $this->priceList = $subtotal->getPriceList();
        }

        return $this;
    }

    /**
     * @return Subtotal
     */
    public function getSubtotal()
    {
        $subtotal = new Subtotal();
        $subtotal->setAmount($this->value)
            ->setCurrency($this->currency)
            ->setType(LineItemNotPricedSubtotalProvider::TYPE)
            ->setLabel(LineItemNotPricedSubtotalProvider::LABEL)
            ->setPriceList($this->combinedPriceList ?? $this->priceList);

        return $subtotal;
    }
}
