<?php

namespace Oro\Bundle\WebsiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\AuditableBusinessUnitAwareTrait;
use Oro\Bundle\WebsiteBundle\Model\ExtendWebsite;

/**
 * @ORM\Table(
 *     name="oro_website",
 *     indexes={
 *          @ORM\Index(name="idx_oro_website_created_at", columns={"created_at"}),
 *          @ORM\Index(name="idx_oro_website_updated_at", columns={"updated_at"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository")
 * @Config(
 *      routeName="oro_websitepro_index",
 *      routeView="oro_websitepro_view",
 *      routeUpdate="oro_websitepro_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-briefcase"
 *          },
 *          "ownership"={
 *              "owner_type"="BUSINESS_UNIT",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="business_unit_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class Website extends ExtendWebsite implements OrganizationAwareInterface
{
    use DatesAwareTrait;
    use AuditableBusinessUnitAwareTrait;

    /**
     * @var Collection|Website[]
     *
     * @ORM\ManyToMany(targetEntity="Website", mappedBy="relatedWebsites")
     */
    protected $inversedWebsites;

    /**
     * @var Collection|Website[]
     *
     * @ORM\ManyToMany(targetEntity="Website", inversedBy="inversedWebsites")
     * @ORM\JoinTable(
     *      name="oro_related_website",
     *      joinColumns={@ORM\JoinColumn(name="website_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="related_website_id", referencedColumnName="id", onDelete="CASCADE")
     *      }
     * )
     */
    protected $relatedWebsites;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $name;

    /**
     * @ORM\Column(name="is_default", type="boolean")
     *
     * @var bool
     */
    protected $default = false;

    /**
     * Website constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->inversedWebsites = new ArrayCollection();
        $this->relatedWebsites = new ArrayCollection();
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|Website[]
     */
    public function getRelatedWebsites()
    {
        return $this->relatedWebsites;
    }

    /**
     * @param Website $website
     * @return $this
     */
    public function addRelatedWebsite(Website $website)
    {
        if (!$this->relatedWebsites->contains($website)) {
            foreach ($this->relatedWebsites as $relatedWebsite) {
                $website->addRelatedWebsite($relatedWebsite);
            }
        }

        if (!$this->relatedWebsites->contains($website)) {
            $this->relatedWebsites->add($website);
            $website->addRelatedWebsite($this);
        }

        return $this;
    }

    /**
     * @param Website $removedWebsite
     * @return $this
     */
    public function removeRelatedWebsite(Website $removedWebsite)
    {
        if ($this->relatedWebsites->contains($removedWebsite)) {
            foreach ($removedWebsite->relatedWebsites as $website) {
                $website->relatedWebsites->removeElement($removedWebsite);
            }

            $removedWebsite->relatedWebsites->clear();
        }

        return $this;
    }

    /**
     * Pre persist event listener
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

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->name;
    }

    /**
     * @return boolean
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * @param boolean $default
     * @return $this
     */
    public function setDefault($default)
    {
        $this->default = (bool)$default;

        return $this;
    }
}
