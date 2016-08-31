<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * @ORM\Table(name="orob2b_price_rule")
 * @ORM\Entity()
 */
class PriceRule
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    protected $currency;

    /**
     * @var string
     *
     * @ORM\Column(name="currency_expression", type="string", length=255, nullable=true)
     */
    protected $currencyExpression;

    /**
     * @var float
     *
     * @ORM\Column(name="quantity", type="float", nullable=true)
     */
    protected $quantity;

    /**
     * @var string
     *
     * @ORM\Column(name="quantity_expression", type="string", length=255, nullable=true)
     */
    protected $quantityExpression;

    /**
     * @var ProductUnit
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\ProductUnit")
     * @ORM\JoinColumn(name="product_unit_id", referencedColumnName="code", onDelete="SET NULL")
     */
    protected $productUnit;

    /**
     * @var string
     *
     * @ORM\Column(name="product_unit_expression", type="string", length=255, nullable=true)
     */
    protected $productUnitExpression;

    /**
     * @var string
     *
     * @ORM\Column(name="rule_condition", type="text", nullable=true)
     */
    protected $ruleCondition;

    /**
     * @var string
     *
     * @ORM\Column(name="rule", type="text", nullable=false)
     */
    protected $rule;

    /**
     * @var Collection|PriceRuleLexeme[]
     *
     * @ORM\OneToMany(
     *      targetEntity="Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme",
     *      mappedBy="priceRule",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     **/
    protected $lexemes;

    /**
     * @var PriceList
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PricingBundle\Entity\PriceList", inversedBy="priceRules")
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     **/
    protected $priceList;

    /**
     * @var int
     *
     * @ORM\Column(name="priority", type="integer")
     */
    protected $priority;

    /**
     * PriceRule constructor
     */
    public function __construct()
    {
        $this->lexemes = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return ProductUnit
     */
    public function getProductUnit()
    {
        return $this->productUnit;
    }

    /**
     * @param ProductUnit $productUnit
     * @return $this
     */
    public function setProductUnit(ProductUnit $productUnit)
    {
        $this->productUnit = $productUnit;

        return $this;
    }

    /**
     * @return string
     */
    public function getRuleCondition()
    {
        return $this->ruleCondition;
    }

    /**
     * @param string $ruleCondition
     * @return $this
     */
    public function setRuleCondition($ruleCondition)
    {
        $this->ruleCondition = $ruleCondition;

        return $this;
    }

    /**
     * @return string
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param string $rule
     * @return $this
     */
    public function setRule($rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * @param PriceList $priceList
     * @return $this
     */
    public function setPriceList(PriceList $priceList)
    {
        $this->priceList = $priceList;

        return $this;
    }

    /**
     * @return PriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return ArrayCollection|PriceRuleLexeme[]
     */
    public function getLexemes()
    {
        return $this->lexemes;
    }

    /**
     * @param ArrayCollection|PriceRuleLexeme[] $lexemes
     * @return $this
     */
    public function setLexemes($lexemes)
    {
        $this->lexemes = $lexemes;

        return $this;
    }

    /**
     * @param PriceRuleLexeme $lexeme
     * @return $this
     */
    public function addLexeme(PriceRuleLexeme $lexeme)
    {
        $lexeme->setPriceRule($this);
        $this->lexemes->add($lexeme);

        return $this;
    }

    /**
     * @param PriceRuleLexeme $lexeme
     * @return $this
     */
    public function removePriceRule(PriceRuleLexeme $lexeme)
    {
        $this->lexemes->removeElement($lexeme);

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrencyExpression()
    {
        return $this->currencyExpression;
    }

    /**
     * @param string $currencyExpression
     * @return $this
     */
    public function setCurrencyExpression($currencyExpression)
    {
        $this->currencyExpression = $currencyExpression;
        
        return $this;
    }

    /**
     * @return string
     */
    public function getQuantityExpression()
    {
        return $this->quantityExpression;
    }

    /**
     * @param string $quantityExpression
     * @return $this
     */
    public function setQuantityExpression($quantityExpression)
    {
        $this->quantityExpression = $quantityExpression;
        
        return $this;
    }

    /**
     * @return string
     */
    public function getProductUnitExpression()
    {
        return $this->productUnitExpression;
    }

    /**
     * @param string $productUnitExpression
     * @return $this
     */
    public function setProductUnitExpression($productUnitExpression)
    {
        $this->productUnitExpression = $productUnitExpression;
        
        return $this;
    }
}
