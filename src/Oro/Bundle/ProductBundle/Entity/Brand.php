<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableTrait;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

use Oro\Bundle\ProductBundle\Model\ExtendBrand;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *      name="oro_brand",
 *      indexes={
 *          @ORM\Index(name="idx_oro_brand_created_at", columns={"created_at"}),
 *          @ORM\Index(name="idx_oro_brand_updated_at", columns={"updated_at"})
 *      }
 * )
 * @ORM\AssociationOverrides({
 *      @ORM\AssociationOverride(
 *          name="slugPrototypes",
 *          joinTable=@ORM\JoinTable(
 *              name="oro_brand_slug_prototype",
 *              joinColumns={
 *                  @ORM\JoinColumn(name="brand_id", referencedColumnName="id", onDelete="CASCADE")
 *              },
 *              inverseJoinColumns={
 *                  @ORM\JoinColumn(
 *                      name="localized_value_id",
 *                      referencedColumnName="id",
 *                      onDelete="CASCADE",
 *                      unique=true
 *                  )
 *              }
 *          )
 *      ),
 *     @ORM\AssociationOverride(
 *          name="slugs",
 *          joinTable=@ORM\JoinTable(
 *              name="oro_brand_slug",
 *              joinColumns={
 *                  @ORM\JoinColumn(name="brand_id", referencedColumnName="id", onDelete="CASCADE")
 *              },
 *              inverseJoinColumns={
 *                  @ORM\JoinColumn(name="slug_id", referencedColumnName="id", unique=true, onDelete="CASCADE")
 *              }
 *          )
 *      )
 * })
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-briefcase"
 *          },
 *          "ownership"={
 *              "owner_type"="BUSINESS_UNIT",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="business_unit_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "form"={
 *              "form_type"="oro_product_brand_select",
 *              "grid_name"="brand-select-grid",
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Brand extends ExtendBrand implements
    OrganizationAwareInterface,
    \JsonSerializable,
    SluggableInterface,
    DatesAwareInterface
{
    use SluggableTrait;

    const STATUS_DISABLED = 'disabled';
    const STATUS_ENABLED = 'enabled';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=16, nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=20
     *          }
     *      }
     *  )
     */
    protected $status = self::STATUS_ENABLED;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime $updatedAt
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    /**
     * @var bool
     */
    protected $updatedAtSet;

    /**
     * @var BusinessUnit
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\BusinessUnit")
     * @ORM\JoinColumn(name="business_unit_owner_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $owner;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $organization;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_brand_name",
     *      joinColumns={
     *          @ORM\JoinColumn(name="brand_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=40,
     *              "full"=true,
     *              "fallback_field"="string"
     *          },
     *          "attribute"={
     *              "is_attribute"=true
     *          }
     *      }
     * )
     */
    protected $names;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_brand_description",
     *      joinColumns={
     *          @ORM\JoinColumn(name="brand_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=60,
     *              "full"=true,
     *              "fallback_field"="text"
     *          },
     *          "attribute"={
     *              "is_attribute"=true
     *          }
     *      }
     * )
     */
    protected $descriptions;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_brand_short_desc",
     *      joinColumns={
     *          @ORM\JoinColumn(name="brand_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=50,
     *              "full"=true,
     *              "fallback_field"="text"
     *          },
     *          "attribute"={
     *              "is_attribute"=true
     *          }
     *      }
     * )
     */
    protected $shortDescriptions;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->names = new ArrayCollection();
        $this->descriptions = new ArrayCollection();
        $this->shortDescriptions = new ArrayCollection();
        $this->slugPrototypes = new ArrayCollection();
        $this->slugs = new ArrayCollection();
        $this->slugPrototypesWithRedirect = new SlugPrototypesWithRedirect($this->slugPrototypes);
    }

    /**
     * @return array
     */
    public static function getStatuses()
    {
        return [self::STATUS_ENABLED, self::STATUS_DISABLED];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            if ($this->getDefaultName()) {
                return (string)$this->getDefaultName();
            } else {
                return (string)$this->id;
            }
        } catch (\LogicException $e) {
            return (string)$this->id;
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return Brand
     */
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return Brand
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUpdatedAtSet()
    {
        return $this->updatedAtSet;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return Brand
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return BusinessUnit
     */
    public function getOwner()
    {
        return $this->owner;
    }
    /**
     * @param BusinessUnit $owningBusinessUnit
     * @return Brand
     */
    public function setOwner($owningBusinessUnit)
    {
        $this->owner = $owningBusinessUnit;
        return $this;
    }
    /**
     * @param OrganizationInterface $organization
     * @return Brand
     */
    public function setOrganization(OrganizationInterface $organization = null)
    {
        $this->organization = $organization;
        return $this;
    }
    /**
     * @return OrganizationInterface
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getNames()
    {
        return $this->names;
    }

    /**
     * @param LocalizedFallbackValue $name
     *
     * @return $this
     */
    public function addName(LocalizedFallbackValue $name)
    {
        if (!$this->names->contains($name)) {
            $this->names->add($name);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $name
     *
     * @return $this
     */
    public function removeName(LocalizedFallbackValue $name)
    {
        if ($this->names->contains($name)) {
            $this->names->removeElement($name);
        }

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getDescriptions()
    {
        return $this->descriptions;
    }

    /**
     * @param LocalizedFallbackValue $description
     *
     * @return $this
     */
    public function addDescription(LocalizedFallbackValue $description)
    {
        if (!$this->descriptions->contains($description)) {
            $this->descriptions->add($description);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $description
     *
     * @return $this
     */
    public function removeDescription(LocalizedFallbackValue $description)
    {
        if ($this->descriptions->contains($description)) {
            $this->descriptions->removeElement($description);
        }

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getShortDescriptions()
    {
        return $this->shortDescriptions;
    }

    /**
     * @param LocalizedFallbackValue $shortDescription
     *
     * @return $this
     */
    public function addShortDescription(LocalizedFallbackValue $shortDescription)
    {
        if (!$this->shortDescriptions->contains($shortDescription)) {
            $this->shortDescriptions->add($shortDescription);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $shortDescription
     *
     * @return $this
     */
    public function removeShortDescription(LocalizedFallbackValue $shortDescription)
    {
        if ($this->shortDescriptions->contains($shortDescription)) {
            $this->shortDescriptions->removeElement($shortDescription);
        }

        return $this;
    }

    /**
     * Pre persist event handler
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Pre update event handler
     *
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->names = new ArrayCollection();
            $this->descriptions = new ArrayCollection();
            $this->shortDescriptions = new ArrayCollection();
            $this->slugPrototypes = new ArrayCollection();
            $this->slugs = new ArrayCollection();
            $this->slugPrototypesWithRedirect = new SlugPrototypesWithRedirect($this->slugPrototypes);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getDefaultName() ? $this->getDefaultName()->getString() : '',
        ];
    }
}
