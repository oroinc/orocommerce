<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orob2b_cpl_activation_rule")
 * @ORM\Entity
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
     */
    public function setCombinedPriceList(CombinedPriceList $combinedPriceList)
    {
        $this->combinedPriceList = $combinedPriceList;
    }

    /**
     * @return mixed
     */
    public function getFullChainPriceList()
    {
        return $this->fullChainPriceList;
    }

    /**
     * @param mixed $fullChainPriceList
     */
    public function setFullChainPriceList(CombinedPriceList $fullChainPriceList)
    {
        $this->fullChainPriceList = $fullChainPriceList;
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
     */
    public function setExpireAt(\DateTime $expireAt = null)
    {
        $this->expireAt = $expireAt;
    }
}
