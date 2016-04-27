<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orob2b_cpl_activation_rule")
 * @ORM\Entity(
 *     repositoryClass="OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository"
 * )
 */
class CombinedPriceListActivationRule
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
     * @var CombinedPriceList
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList")
     * @ORM\JoinColumn(name="combined_price_list_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $combinedPriceList;

    /**
     * @var CombinedPriceList
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList")
     * @ORM\JoinColumn(name="full_combined_price_list_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $fullChainPriceList;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="expire_at", type="datetime", nullable=true)
     */
    protected $expireAt;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="activate_at", type="datetime", nullable=true)
     */
    protected $activateAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=false)
     */
    protected $active = false;

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
