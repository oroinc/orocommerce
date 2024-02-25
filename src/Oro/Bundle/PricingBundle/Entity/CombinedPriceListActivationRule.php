<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;

/**
* Entity that represents Combined Price List Activation Rule
*
*/
#[ORM\Entity(repositoryClass: CombinedPriceListActivationRuleRepository::class)]
#[ORM\Table(name: 'oro_cpl_activation_rule')]
class CombinedPriceListActivationRule
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CombinedPriceList::class)]
    #[ORM\JoinColumn(name: 'combined_price_list_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?CombinedPriceList $combinedPriceList = null;

    #[ORM\ManyToOne(targetEntity: CombinedPriceList::class)]
    #[ORM\JoinColumn(
        name: 'full_combined_price_list_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'CASCADE'
    )]
    protected ?CombinedPriceList $fullChainPriceList = null;

    #[ORM\Column(name: 'expire_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $expireAt = null;

    #[ORM\Column(name: 'activate_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $activateAt = null;

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN, nullable: false)]
    protected ?bool $active = false;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return CombinedPriceList
     */
    public function getCombinedPriceList()
    {
        return $this->combinedPriceList;
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @return $this
     */
    public function setCombinedPriceList(CombinedPriceList $combinedPriceList)
    {
        $this->combinedPriceList = $combinedPriceList;

        return $this;
    }

    /**
     * @return CombinedPriceList
     */
    public function getFullChainPriceList()
    {
        return $this->fullChainPriceList;
    }

    /**
     * @param CombinedPriceList $fullChainPriceList
     * @return $this
     */
    public function setFullChainPriceList(CombinedPriceList $fullChainPriceList)
    {
        $this->fullChainPriceList = $fullChainPriceList;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getExpireAt()
    {
        return $this->expireAt;
    }

    /**
     * @param \DateTime|null $expireAt
     * @return $this
     */
    public function setExpireAt(\DateTime $expireAt = null)
    {
        $this->expireAt = $expireAt;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getActivateAt()
    {
        return $this->activateAt;
    }

    /**
     * @param \DateTime|null $activateAt
     * @return $this
     */
    public function setActivateAt(\DateTime $activateAt = null)
    {
        $this->activateAt = $activateAt;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }
}
