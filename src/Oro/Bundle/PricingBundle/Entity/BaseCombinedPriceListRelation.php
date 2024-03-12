<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

/**
 * Base class of price list relations.
 */
#[ORM\MappedSuperclass]
class BaseCombinedPriceListRelation implements WebsiteAwareInterface
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CombinedPriceList::class)]
    #[ORM\JoinColumn(name: 'combined_price_list_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?CombinedPriceList $priceList = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(name: 'website_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Website $website = null;

    #[ORM\ManyToOne(targetEntity: CombinedPriceList::class)]
    #[ORM\JoinColumn(
        name: 'full_combined_price_list_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    protected ?CombinedPriceList $fullChainPriceList = null;

    #[ORM\Column(name: 'version', type: Types::INTEGER, nullable: true)]
    protected ?int $version = null;

    /**
     * @return CombinedPriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @param CombinedPriceList $priceList
     * @return $this
     */
    public function setPriceList(CombinedPriceList $priceList)
    {
        $this->priceList = $priceList;
        if ($this->fullChainPriceList === null) {
            $this->fullChainPriceList = $priceList;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * {@inheritdoc}
     */
    public function setWebsite(Website $website)
    {
        $this->website = $website;

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

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(?int $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
