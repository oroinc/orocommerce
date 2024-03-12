<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutSubtotalRepository;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;

/**
 * Entity holds checkout subtotals data by currency
 * If isValid=false values should be recalculated
 **/
#[ORM\Entity(repositoryClass: CheckoutSubtotalRepository::class)]
#[ORM\Table(name: 'oro_checkout_subtotal')]
#[ORM\Index(columns: ['is_valid'], name: 'idx_checkout_subtotal_valid')]
#[ORM\UniqueConstraint(name: 'unique_checkout_currency', columns: ['checkout_id', 'currency'])]
class CheckoutSubtotal
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Checkout::class, inversedBy: 'subtotals')]
    #[ORM\JoinColumn(name: 'checkout_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Checkout $checkout = null;

    #[ORM\Column(name: 'currency', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $currency = null;

    /**
     * @var float
     */
    #[ORM\Column(name: 'value', type: 'money', nullable: true)]
    protected $value;

    #[ORM\ManyToOne(targetEntity: CombinedPriceList::class)]
    #[ORM\JoinColumn(name: 'combined_price_list_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?CombinedPriceList $combinedPriceList = null;

    #[ORM\ManyToOne(targetEntity: PriceList::class)]
    #[ORM\JoinColumn(name: 'price_list_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?PriceList $priceList = null;

    #[ORM\Column(name: 'is_valid', type: Types::BOOLEAN)]
    protected ?bool $valid = false;

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
