<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orob2b_price_rule_lexeme")
 * @ORM\Entity()
 */
class PriceRuleLexeme
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
     * @var string
     *
     * @ORM\Column(name="class_name", type="string", length=255, nullable=false)
     */
    protected $className;

    /**
     * @var string
     *
     * @ORM\Column(name="field_name", type="string", length=255, nullable=false)
     */
    protected $fieldName;

    /**
     * @var PriceRule
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\PricingBundle\Entity\PriceRule", inversedBy="lexemes")
     * @ORM\JoinColumn(name="price_rule_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     **/
    protected $priceRule;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param PriceRule $priceRule
     */
    public function setPriceRule($priceRule)
    {
        $this->priceRule = $priceRule;
    }

    /**
     * @return PriceRule
     */
    public function getPriceRule()
    {
        return $this->priceRule;
    }

    /**
     * @param string $className
     * @return $this
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $fieldName
     * @return $this
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }
}
