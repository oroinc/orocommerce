<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;

/**
* Entity that represents Price Rule Lexeme
*
*/
#[ORM\Entity(repositoryClass: PriceRuleLexemeRepository::class)]
#[ORM\Table(name: 'oro_price_rule_lexeme')]
class PriceRuleLexeme
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'class_name', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $className = null;

    #[ORM\Column(name: 'field_name', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $fieldName = null;

    #[ORM\ManyToOne(targetEntity: PriceRule::class, inversedBy: 'lexemes')]
    #[ORM\JoinColumn(name: 'price_rule_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?PriceRule $priceRule = null;

    #[ORM\ManyToOne(targetEntity: PriceList::class, inversedBy: 'priceRules')]
    #[ORM\JoinColumn(name: 'price_list_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?PriceList $priceList = null;

    #[ORM\Column(name: 'relation_id', type: Types::INTEGER, nullable: true)]
    protected ?int $relationId = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param PriceRule|null $priceRule
     * @return $this
     */
    public function setPriceRule(?PriceRule $priceRule = null)
    {
        $this->priceRule = $priceRule;

        return $this;
    }

    /**
     * @return PriceRule|null
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

    /**
     * @return PriceList|null
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @param PriceList|null $priceList
     * @return $this
     */
    public function setPriceList(?PriceList $priceList = null)
    {
        $this->priceList = $priceList;

        return $this;
    }

    /**
     * @return int
     */
    public function getRelationId()
    {
        return $this->relationId;
    }

    /**
     * @param int $relationId
     * @return $this
     */
    public function setRelationId($relationId)
    {
        $this->relationId = $relationId;

        return $this;
    }
}
