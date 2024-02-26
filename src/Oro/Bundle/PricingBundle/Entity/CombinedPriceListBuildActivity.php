<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareTrait;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListBuildActivityRepository;

/**
 * ORM entity for storing information about current build status of combined price list.
 * Presence of records indicated scheduled not finished changes and that CPL can't be used as fallback CPL.
 *
 * @see Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository::findFallbackCpl
 * @see Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository::findFallbackCplUsingMergeFlag
 */
#[ORM\Entity(repositoryClass: CombinedPriceListBuildActivityRepository::class)]
#[ORM\Table(name: 'oro_price_list_combined_build_activity')]
#[ORM\Index(columns: ['parent_job_id'], name: 'oro_cpl_build_activity_job_idx')]
class CombinedPriceListBuildActivity implements CreatedAtAwareInterface
{
    use CreatedAtAwareTrait;

    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::BIGINT)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    #[ORM\ManyToOne(targetEntity: CombinedPriceList::class)]
    #[ORM\JoinColumn(name: 'combined_price_list_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?CombinedPriceList $combinedPriceList = null;

    #[ORM\Column(name: 'parent_job_id', type: Types::INTEGER, nullable: true)]
    protected ?int $parentJobId = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCombinedPriceList(): ?CombinedPriceList
    {
        return $this->combinedPriceList;
    }

    public function setCombinedPriceList(CombinedPriceList $combinedPriceList): static
    {
        $this->combinedPriceList = $combinedPriceList;

        return $this;
    }

    public function getParentJobId(): ?int
    {
        return $this->parentJobId;
    }

    public function setParentJobId(?int $jobId): static
    {
        $this->parentJobId = $jobId;

        return $this;
    }
}
