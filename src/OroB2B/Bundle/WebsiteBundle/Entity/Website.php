<?php

namespace OroB2B\Bundle\WebsiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\AuditableBusinessUnitAwareTrait;

use OroB2B\Bundle\WebsiteBundle\Model\ExtendWebsite;

/**
 * @ORM\Table(
 *     name="orob2b_website",
 *     indexes={
 *          @ORM\Index(name="idx_orob2b_website_created_at", columns={"created_at"}),
 *          @ORM\Index(name="idx_orob2b_website_updated_at", columns={"updated_at"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository")
 * @Config(
 *      routeName="orob2b_website_index",
 *      routeView="orob2b_website_view",
 *      routeUpdate="orob2b_website_update",
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
     * @var Collection|Locale[]
     *
     * @ORM\ManyToMany(targetEntity="Locale", inversedBy="websites")
     * @ORM\JoinTable(name="orob2b_websites_locales")
     */
    protected $locales;

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
     *      name="orob2b_related_website",
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
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $url;

    /**
     * Website constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->inversedWebsites = new ArrayCollection();
        $this->relatedWebsites = new ArrayCollection();
        $this->locales = new ArrayCollection();
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
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

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
     * Get website locales
     *
     * @return ArrayCollection|Locale[]
     */
    public function getLocales()
    {
        return $this->locales;
    }

    /**
     * Set website locales
     *
     * @param ArrayCollection|Locale[] $locales
     *
     * @return Website
     */
    public function resetLocales($locales)
    {
        $this->locales->clear();

        foreach ($locales as $locale) {
            $this->addLocale($locale);
        }

        return $this;
    }

    /**
     * @param Locale $locale
     *
     * @return Website
     */
    public function addLocale(Locale $locale)
    {
        if (!$this->locales->contains($locale)) {
            $this->locales->add($locale);
        }

        return $this;
    }

    /**
     * @param Locale $locale
     *
     * @return Website
     */
    public function removeLocale(Locale $locale)
    {
        if ($this->locales->contains($locale)) {
            $this->locales->removeElement($locale);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->name;
    }
}
