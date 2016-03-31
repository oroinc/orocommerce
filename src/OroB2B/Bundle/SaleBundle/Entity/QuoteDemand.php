<?php

namespace OroB2B\Bundle\SaleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use OroB2B\Bundle\OrderBundle\Model\ShippingAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use OroB2B\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 *
 * @ORM\Table(name="orob2b_quote_demand")
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-list-alt"
 *          }
 *      }
 * )
 */
class QuoteDemand implements CheckoutSourceEntityInterface, LineItemsAwareInterface, ShippingAwareInterface
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Quote
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\SaleBundle\Entity\Quote")
     * @ORM\JoinColumn(name="quote_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $quote;

    /**
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\SaleBundle\Entity\QuoteProductDemand",
     *     mappedBy="quoteDemand", cascade={"all"})
     */
    protected $demandProducts;

    public function __construct()
    {
        $this->demandProducts = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Quote
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * @param Quote $quote
     */
    public function setQuote(Quote $quote)
    {
        $this->quote = $quote;
    }

    /**
     * @return QuoteProductDemand[]|Collection
     */
    public function getDemandProducts()
    {
        return $this->demandProducts;
    }

    /**
     * @param QuoteProductDemand $demandOffer
     * @return $this
     */
    public function addDemandOffer(QuoteProductDemand $demandOffer)
    {
        if (!$this->demandProducts->contains($demandOffer)) {
            $this->demandProducts->add($demandOffer);
        }
        return $this;
    }

    /**
     * @param QuoteProductDemand $demandOffer
     * @return $this
     */
    public function removeDemandOffer(QuoteProductDemand $demandOffer)
    {
        if ($this->demandProducts->contains($demandOffer)) {
            $this->demandProducts->remove($demandOffer);
        }
        return $this;
    }

    /**
     * @return ArrayCollection|QuoteProductDemand[]
     */
    public function getLineItems()
    {
        return $this->demandProducts;
    }

    /**
     * @return Price|null
     */
    public function getShippingCost()
    {
        if ($this->quote) {
            return $this->quote->getShippingEstimate();
        }
        return null;
    }
}
