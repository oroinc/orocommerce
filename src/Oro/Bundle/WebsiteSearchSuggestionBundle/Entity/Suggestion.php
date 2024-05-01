<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Represents suggestions words that can be used to search products on website
 *
 * @ORM\Table(
 *     name="oro_website_search_suggestion",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="suggestion_unique",
 *              columns={"phrase", "localization_id", "organization_id"}
 *          )
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\SuggestionRepository")
 * @Config(
 *     defaultValues={
 *         "ownership"={
 *             "owner_type"="ORGANIZATION",
 *             "owner_field_name"="organization",
 *             "owner_column_name"="organization_id",
 *         },
 *         "dataaudit"={
 *             "auditable"=false
 *         },
 *     }
 * )
 */
class Suggestion implements ExtendEntityInterface, CreatedAtAwareInterface
{
    use ExtendEntityTrait;
    use CreatedAtAwareTrait;

    /**
     * Stores search suggestion
     */
    public const HINT_SEARCH_SUGGESTION = 'search_suggestion';

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="phrase", type="string", nullable=false)
     */
    protected string $phrase;

    /**
     * @ORM\Column(name="words_count", type="smallint", nullable=false)
     */
    protected int $wordsCount;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected Organization $organization;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\LocaleBundle\Entity\Localization")
     * @ORM\JoinColumn(name="localization_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected ?Localization $localization = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function getPhrase(): string
    {
        return $this->phrase;
    }

    public function setPhrase(string $phrase): void
    {
        $this->phrase = $phrase;
    }

    public function getWordsCount(): int
    {
        return $this->wordsCount;
    }

    public function setWordsCount(int $wordsCount): void
    {
        $this->wordsCount = $wordsCount;
    }

    public function getLocalization(): ?Localization
    {
        return $this->localization;
    }

    public function setLocalization(?Localization $localization): void
    {
        $this->localization = $localization;
    }
}
