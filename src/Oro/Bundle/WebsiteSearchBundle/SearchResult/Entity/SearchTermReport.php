<?php

namespace Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\BusinessUnitAwareTrait;

/**
 * ORM Entity SearchTermReport.
 *
 * @ORM\Entity(
 *     repositoryClass="Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\Repository\SearchTermReportRepository"
 * )
 * @ORM\Table(
 *     name="oro_website_search_term_report",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *              name="website_search_term_report_term_unq",
 *              columns={"search_date", "normalized_search_term_hash"}
 *         )
 *     },
 *     indexes={
 *         @ORM\Index(name="website_search_term_report_date_idx", columns={"search_date"}),
 *         @ORM\Index(name="website_search_term_report_organization_id_idx", columns={"id", "organization_id"})
 *     }
 * )
 * @Config(
 *     defaultValues={
 *         "entity"={
 *             "icon"="fa-search"
 *         },
 *         "ownership"={
 *             "owner_type"="BUSINESS_UNIT",
 *             "owner_field_name"="owner",
 *             "owner_column_name"="business_unit_owner_id",
 *             "organization_field_name"="organization",
 *             "organization_column_name"="organization_id"
 *         },
 *         "security"={
 *             "type"="ACL",
 *             "group_name"="commerce",
 *             "category"="search"
 *         }
 *     }
 * )
 */
class SearchTermReport implements
    ExtendEntityInterface,
    OrganizationAwareInterface
{
    use ExtendEntityTrait;
    use BusinessUnitAwareTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @ORM\Column(
     *     name="search_term",
     *     type="string",
     *     length=255,
     *     nullable=false
     * )
     */
    private $searchTerm;

    /**
     * @ORM\Column(
     *     name="normalized_search_term_hash",
     *     type="string",
     *     length=32,
     *     nullable=false
     * )
     */
    private $normalizedSearchTermHash;

    /**
     * @ORM\Column(
     *     name="times_searched",
     *     type="integer",
     *     nullable=false
     * )
     */
    private $timesSearched;

    /**
     * @ORM\Column(
     *     name="times_returned_results",
     *     type="integer",
     *     nullable=false
     * )
     */
    private $timesReturnedResults;

    /**
     * @ORM\Column(
     *     name="times_empty",
     *     type="integer",
     *     nullable=false
     * )
     */
    private $timesEmpty;

    /**
     * @ORM\Column(
     *     name="search_date",
     *     type="date",
     *     nullable=false
     * )
     */
    private $searchDate;

    /**
     * @ORM\Column(
     *     name="search_date_day",
     *     type="integer",
     *     nullable=false
     * )
     */
    private $searchDateDay;

    /**
     * @ORM\Column(
     *     name="search_date_month",
     *     type="integer",
     *     nullable=false
     * )
     */
    private $searchDateMonth;

    /**
     * @ORM\Column(
     *     name="search_date_quarter",
     *     type="integer",
     *     nullable=false
     * )
     */
    private $searchDateQuarter;

    /**
     * @ORM\Column(
     *     name="search_date_year",
     *     type="integer",
     *     nullable=false
     * )
     */
    private $searchDateYear;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getSearchTerm(): ?string
    {
        return $this->searchTerm;
    }

    public function setSearchTerm(string $searchTerm): self
    {
        $this->searchTerm = $searchTerm;

        return $this;
    }

    public function getNormalizedSearchTermHash(): ?string
    {
        return $this->normalizedSearchTermHash;
    }

    public function setNormalizedSearchTermHash(string $normalizedSearchTermHash): self
    {
        $this->normalizedSearchTermHash = $normalizedSearchTermHash;

        return $this;
    }

    public function getTimesSearched(): ?int
    {
        return $this->timesSearched;
    }

    public function setTimesSearched(int $timesSearched): self
    {
        $this->timesSearched = $timesSearched;

        return $this;
    }

    public function getTimesReturnedResults(): ?int
    {
        return $this->timesReturnedResults;
    }

    public function setTimesReturnedResults(int $timesReturnedResults): self
    {
        $this->timesReturnedResults = $timesReturnedResults;

        return $this;
    }

    public function getTimesEmpty(): ?int
    {
        return $this->timesEmpty;
    }

    public function setTimesEmpty(int $timesEmpty): self
    {
        $this->timesEmpty = $timesEmpty;

        return $this;
    }

    public function getSearchDate(): ?\DateTimeInterface
    {
        return $this->searchDate;
    }

    public function setSearchDate(\DateTimeInterface $searchDate): self
    {
        $this->searchDate = $searchDate;

        $this->searchDateDay = (int) $searchDate->format('d');
        $this->searchDateMonth = (int) $searchDate->format('m');
        $this->searchDateYear = (int) $searchDate->format('Y');
        $this->searchDateQuarter = (int) ceil($this->searchDateMonth / 3.0);

        return $this;
    }

    public function getSearchDateDay(): int
    {
        return $this->searchDateDay;
    }

    public function getSearchDateMonth(): int
    {
        return $this->searchDateMonth;
    }

    public function getSearchDateQuarter(): int
    {
        return $this->searchDateQuarter;
    }

    public function getSearchDateYear(): int
    {
        return $this->searchDateYear;
    }
}
