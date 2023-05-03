<?php

namespace Oro\Bundle\CMSBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
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
 * @ORM\Entity(repositoryClass="Oro\Bundle\CMSBundle\Entity\Repository\ContentBlockRepository")
 * @ORM\Table(name="oro_cms_content_block")
 * @Config(
 *      routeName="oro_cms_content_block_index",
 *      routeView="oro_cms_content_block_view",
 *      routeUpdate="oro_cms_content_block_update",
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="BUSINESS_UNIT",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="business_unit_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *     }
 * )
 * @method LocalizedFallbackValue getDefaultTitle()
 * @method LocalizedFallbackValue getTitle(Localization $localization = null)
 */
class ContentBlock implements
    DatesAwareInterface,
    OrganizationAwareInterface,
    ExtendEntityInterface
{
    use BusinessUnitAwareTrait;
    use DatesAwareTrait;
    use ExtendEntityTrait;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100, nullable=false, unique=true)
     */
    protected $alias;

    /**
     * @var ArrayCollection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_cms_content_block_title",
     *      joinColumns={
     *          @ORM\JoinColumn(name="content_block_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $titles;

    /**
     * @var ArrayCollection|Scope[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\ScopeBundle\Entity\Scope",
     *      fetch="EXTRA_LAZY"
     * )
     * @ORM\JoinTable(name="oro_cms_content_block_scope",
     *      joinColumns={
     *          @ORM\JoinColumn(name="content_block_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="scope_id", referencedColumnName="id", onDelete="CASCADE")
     *      }
     * )
     */
    protected $scopes;

    /**
     * @var ArrayCollection|TextContentVariant[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\CMSBundle\Entity\TextContentVariant",
     *     mappedBy="contentBlock",
     *     cascade={"ALL"},
     *     orphanRemoval=true
     * )
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "actualize_owning_side_on_change"=true
     *          }
     *      }
     * )
     */
    protected $contentVariants;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default"=true})
     */
    protected $enabled = true;

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
