<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareTrait;

/**
 * ORM entity for storing information about current build status of combined price list.
 * Presence of records indicated scheduled not finished changes and that CPL can't be used as fallback CPL.
 *
 * @see Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository::findFallbackCpl
 * @see Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository::findFallbackCplUsingMergeFlag
 *
 * @ORM\Table(
 *     name="oro_price_list_combined_build_activity",
 *     indexes={
 *         @ORM\Index(
 *              name="oro_cpl_build_activity_job_idx",
 *              columns={
 *                  "parent_job_id"
 *              }
 *         )
 *     }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListBuildActivityRepository")
 */
class CombinedPriceListBuildActivity implements CreatedAtAwareInterface
{
    use CreatedAtAwareTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var CombinedPriceList
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PricingBundle\Entity\CombinedPriceList")
     * @ORM\JoinColumn(name="combined_price_list_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $combinedPriceList;

    /**
     * @var int|null
     *
     * @ORM\Column(name="parent_job_id", type="integer", nullable=true)
     */
    protected $parentJobId;

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
