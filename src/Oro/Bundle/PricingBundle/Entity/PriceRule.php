<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleRepository;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * This entity represents price rule for price list
 */
#[ORM\Entity(repositoryClass: PriceRuleRepository::class)]
#[ORM\Table(name: 'oro_price_rule')]
class PriceRule
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'currency', type: Types::STRING, length: 3, nullable: true)]
    protected ?string $currency = null;

    #[ORM\Column(name: 'currency_expression', type: Types::TEXT, nullable: true)]
    protected ?string $currencyExpression = null;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'quantity', type: Types::FLOAT, nullable: true)]
    protected $quantity;

    #[ORM\Column(name: 'quantity_expression', type: Types::TEXT, nullable: true)]
    protected ?string $quantityExpression = null;

    #[ORM\ManyToOne(targetEntity: ProductUnit::class)]
    #[ORM\JoinColumn(name: 'product_unit_id', referencedColumnName: 'code', nullable: true, onDelete: 'CASCADE')]
    protected ?ProductUnit $productUnit = null;

    #[ORM\Column(name: 'product_unit_expression', type: Types::TEXT, nullable: true)]
    protected ?string $productUnitExpression = null;

    #[ORM\Column(name: 'rule_condition', type: Types::TEXT, nullable: true)]
    protected ?string $ruleCondition = null;

    #[ORM\Column(name: 'rule', type: Types::TEXT, nullable: false)]
    protected ?string $rule = null;

    /**
     * @var Collection<int, PriceRuleLexeme>
     **/
    #[ORM\OneToMany(mappedBy: 'priceRule', targetEntity: PriceRuleLexeme::class, cascade: ['ALL'], orphanRemoval: true)]
    protected ?Collection $lexemes = null;

    #[ORM\ManyToOne(targetEntity: PriceList::class, inversedBy: 'priceRules')]
    #[ORM\JoinColumn(name: 'price_list_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?PriceList $priceList = null;

    #[ORM\Column(name: 'priority', type: Types::INTEGER)]
    protected ?int $priority = null;

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
     * @param ProductUnit|null $productUnit
     * @return $this
     */
    public function setProductUnit(ProductUnit $productUnit = null)
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
