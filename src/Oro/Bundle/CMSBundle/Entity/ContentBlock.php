<?php

namespace Oro\Bundle\CMSBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroCMSBundle_Entity_ContentBlock;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentBlockRepository;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\BusinessUnitAwareTrait;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * ContentBlock ORM entity.
 *
 * @method LocalizedFallbackValue getDefaultTitle()
 * @method LocalizedFallbackValue getTitle(Localization $localization = null)
 * @mixin OroCMSBundle_Entity_ContentBlock
 */
#[ORM\Entity(repositoryClass: ContentBlockRepository::class)]
#[ORM\Table(name: 'oro_cms_content_block')]
#[Config(
    routeName: 'oro_cms_content_block_index',
    routeView: 'oro_cms_content_block_view',
    routeUpdate: 'oro_cms_content_block_update',
    defaultValues: [
        'ownership' => [
            'owner_type' => 'BUSINESS_UNIT',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'business_unit_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '']
    ]
)]
class ContentBlock implements
    DatesAwareInterface,
    OrganizationAwareInterface,
    ExtendEntityInterface
{
    use BusinessUnitAwareTrait;
    use DatesAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true, nullable: false)]
    protected ?string $alias = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'oro_cms_content_block_title')]
    #[ORM\JoinColumn(name: 'content_block_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Collection $titles = null;

    /**
     * @var Collection<int, Scope>
     */
    #[ORM\ManyToMany(targetEntity: Scope::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinTable(name: 'oro_cms_content_block_scope')]
    #[ORM\JoinColumn(name: 'content_block_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'scope_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $scopes = null;

    /**
     * @var Collection<int, TextContentVariant>
     */
    #[ORM\OneToMany(
        mappedBy: 'contentBlock',
        targetEntity: TextContentVariant::class,
        cascade: ['ALL'],
        orphanRemoval: true
    )]
    #[ConfigField(defaultValues: ['entity' => ['actualize_owning_side_on_change' => true]])]
    protected ?Collection $contentVariants = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    protected ?bool $enabled = true;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->titles = new ArrayCollection();
        $this->scopes = new ArrayCollection();
        $this->contentVariants = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     *
     * @return ContentBlock
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return ArrayCollection|LocalizedFallbackValue[]
     */
    public function getTitles()
    {
        return $this->titles;
    }

    /**
     * @param LocalizedFallbackValue $title
     *
     * @return ContentBlock
     */
    public function addTitle(LocalizedFallbackValue $title)
    {
        if (!$this->titles->contains($title)) {
            $this->titles->add($title);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $title
     *
     * @return ContentBlock
     */
    public function removeTitle(LocalizedFallbackValue $title)
    {
        if ($this->titles->contains($title)) {
            $this->titles->removeElement($title);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * @param Scope $scope
     *
     * @return ContentBlock
     */
    public function addScope(Scope $scope)
    {
        if (!$this->scopes->contains($scope)) {
            $this->scopes->add($scope);
        }

        return $this;
    }

    /**
     * @param Scope $scope
     *
     * @return ContentBlock
     */
    public function removeScope(Scope $scope)
    {
        if ($this->scopes->contains($scope)) {
            $this->scopes->removeElement($scope);
        }

        return $this;
    }

    /**
     * @return ContentBlock
     */
    public function resetScopes()
    {
        $this->scopes->clear();

        return $this;
    }

    /**
     * @return ArrayCollection|TextContentVariant[]
     */
    public function getContentVariants()
    {
        return $this->contentVariants;
    }

    /**
     * @param TextContentVariant $contentVariant
     *
     * @return ContentBlock
     */
    public function addContentVariant(TextContentVariant $contentVariant)
    {
        if (!$this->contentVariants->contains($contentVariant)) {
            $contentVariant->setContentBlock($this);
            $this->contentVariants->add($contentVariant);
        }

        return $this;
    }

    /**
     * @param TextContentVariant $contentVariant
     *
     * @return ContentBlock
     */
    public function removeContentVariant(TextContentVariant $contentVariant)
    {
        if ($this->contentVariants->contains($contentVariant)) {
            $this->contentVariants->removeElement($contentVariant);
        }

        return $this;
    }

    /**
     * Get default variant.
     *
     * Prefer to use TextContentVariantRepository::getDefaultContentVariantForContentBlock
     * to avoid contentVariants collection loading
     *
     * @return TextContentVariant|null
     */
    public function getDefaultVariant()
    {
        foreach ($this->contentVariants as $variant) {
            if ($variant->isDefault()) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return ContentBlock
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool)$enabled;

        return $this;
    }
}
