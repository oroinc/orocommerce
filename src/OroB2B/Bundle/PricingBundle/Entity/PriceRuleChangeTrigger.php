<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @ORM\Table(name="orob2b_price_rule_ch_trigger")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\PricingBundle\Entity\Repository\PriceRuleChangeTriggerRepository")
 */
class PriceRuleChangeTrigger
{
    /**
     * @var integer $id
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var PriceRule
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\PricingBundle\Entity\PriceRule")
     * @ORM\JoinColumn(name="price_rule_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $priceRule;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $product;

    /**
     * @param PriceRule $priceRule
     * @param Product $product
     */
    public function __construct(PriceRule $priceRule, Product $product = null)
    {
        $this->priceRule = $priceRule;
        $this->product = $product;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * @return PriceRule
     */
    public function getPriceRule()
    {
        return $this->priceRule;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }
}
