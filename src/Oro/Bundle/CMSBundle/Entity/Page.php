<?php

namespace Oro\Bundle\CMSBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroCMSBundle_Entity_Page;
use Oro\Bundle\CMSBundle\Entity\Repository\PageRepository;
use Oro\Bundle\CMSBundle\Form\Type\PageSelectType;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Entity\DraftableTrait;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\AuditableOrganizationAwareTrait;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableTrait;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;

/**
 * Represents CMS Page
 *
 * @method LocalizedFallbackValue getTitle(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultTitle()
 * @method LocalizedFallbackValue getSlug(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultSlug()
 * @method setDefaultTitle($title)
 * @method setDefaultSlug($slug)
 * @method $this cloneLocalizedFallbackValueAssociations()
 * @mixin OroCMSBundle_Entity_Page
 */
#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\Table(name: 'oro_cms_page')]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'slugPrototypes',
        joinColumns: [
        new ORM\JoinColumn(
            name: 'page_id',
            referencedColumnName: 'id',
            onDelete: 'CASCADE'
        )
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(
                name: 'localized_value_id',
                referencedColumnName: 'id',
                unique: true,
                onDelete: 'CASCADE'
            )
        ],
        joinTable: new ORM\JoinTable(name: 'oro_cms_page_slug_prototype')
    ),
    new ORM\AssociationOverride(
        name: 'slugs',
        joinColumns: [
        new ORM\JoinColumn(
            name: 'page_id',
            referencedColumnName: 'id',
            onDelete: 'CASCADE'
        )
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(
                name: 'slug_id',
                referencedColumnName: 'id',
                unique: true,
                onDelete: 'CASCADE'
            )
        ],
        joinTable: new ORM\JoinTable(name: 'oro_cms_page_to_slug')
    )
])]
#[Config(
    routeName: 'oro_cms_page_index',
    routeView: 'oro_cms_page_view',
    routeUpdate: 'oro_cms_page_update',
    defaultValues: [
        'entity' => ['icon' => 'fa-book'],
        'dataaudit' => ['auditable' => true],
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'organization',
            'owner_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => ''],
        'form' => ['form_type' => PageSelectType::class, 'grid_name' => 'cms-page-select-grid'],
        'draft' => ['draftable' => true],
        'slug' => ['source' => 'titles']
    ]
)]
class Page implements
    DatesAwareInterface,
    SluggableInterface,
    DraftableInterface,
    OrganizationAwareInterface,
    ExtendEntityInterface
{
    use AuditableOrganizationAwareTrait;
    use DatesAwareTrait;
    use SluggableTrait;
    use DraftableTrait;
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'oro_cms_page_title')]
    #[ORM\JoinColumn(name: 'page_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'draft' => ['draftable' => true]])]
    protected ?Collection $titles = null;

    /**
     * @var string
     */
    #[ORM\Column(type: 'wysiwyg', nullable: true)]
    #[ConfigField(
        defaultValues: [
            'dataaudit' => ['auditable' => false],
            'attachment' => ['acl_protected' => false],
            'draft' => ['draftable' => true]
        ]
    )]
    protected $content;

    #[ORM\Column(name: 'do_not_render_title', type: Types::BOOLEAN, options: ['default' => false])]
    protected bool $doNotRenderTitle = false;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->slugPrototypes = new ArrayCollection();
        $this->slugs = new ArrayCollection();
        $this->titles = new ArrayCollection();
        $this->slugPrototypesWithRedirect = new SlugPrototypesWithRedirect($this->slugPrototypes);
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getTitles()
    {
        return $this->titles;
    }

    /**
     * @param LocalizedFallbackValue $title
     *
     * @return $this
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
     * @return $this
     */
    public function removeTitle(LocalizedFallbackValue $title)
    {
        if ($this->titles->contains($title)) {
            $this->titles->removeElement($title);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    public function clearSlugs(): void
    {
        $this->resetSlugs();
        $this->resetSlugPrototypes();
        $this->setSlugPrototypesWithRedirect(
            new SlugPrototypesWithRedirect(
                $this->slugPrototypes,
                $this->getSlugPrototypesWithRedirect()?->getCreateRedirect()
            )
        );
    }

    public function setDoNotRenderTitle(bool $doNotRenderTitle): self
    {
        $this->doNotRenderTitle = $doNotRenderTitle;

        return $this;
    }

    public function isDoNotRenderTitle(): bool
    {
        return $this->doNotRenderTitle;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getDefaultTitle();
    }

    public function __clone()
    {
        if ($this->id) {
            $this->cloneExtendEntityStorage();
        }
    }
}
