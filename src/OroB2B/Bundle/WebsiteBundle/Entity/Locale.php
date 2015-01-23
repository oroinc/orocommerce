<?php

namespace OroB2B\Bundle\WebsiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Table(name="orob2b_locale")
 * @ORM\Entity
 * @Config(
 *      routeName="orob2b_locale_index",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-flag"
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
class Locale
{
    /**
     * @ORM\ManyToMany(targetEntity="Website", mappedBy="locales")
     */
    private $websites;

    /**
     * @ORM\OneToMany(targetEntity="Locale", mappedBy="parentLocale")
     */
    private $childLocales;

    /**
     * @ORM\ManyToOne(targetEntity="Locale", inversedBy="childLocales")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parentLocale;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=10, unique=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $code;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime $updatedAt
     *
     * @ORM\Column(type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    public function __construct()
    {
        $this->childLocales = new ArrayCollection();
        $this->websites = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
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
     * @return $this
     */
    public function setCreatedAt($createdAt)
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
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get child locales
     *
     * @return ArrayCollection|Locale[]
     */
    public function getChildLocales()
    {
        return $this->childLocales;
    }

    /**
     * Set children locales
     *
     * @param ArrayCollection|Locale[] $locales
     *
     * @return Locale
     */
    public function resetLocales($locales)
    {
        $this->childLocales->clear();

        foreach ($locales as $locale) {
            $this->addChildLocale($locale);
        }

        return $this;
    }

    /**
     * Add child locale
     *
     * @param Locale $locale
     * @return Locale
     */
    public function addChildLocale(Locale $locale)
    {
        if (!$this->childLocales->contains($locale)) {
            $this->childLocales->add($locale);
            $locale->setParentLocale($this);
        }

        return $this;
    }

    /**
     * Remove child locale
     *
     * @param Locale $locale
     * @return Locale
     */
    public function removeChildLocale(Locale $locale)
    {
        if ($this->childLocales->contains($locale)) {
            $this->childLocales->removeElement($locale);
            $locale->setParentLocale(null);
        }

        return $this;
    }

    /**
     * @return Locale
     */
    public function getParentLocale()
    {
        return $this->parentLocale;
    }

    /**
     * @param Locale $parentLocale
     * @return $this
     */
    public function setParentLocale(Locale $parentLocale = null)
    {
        $this->parentLocale = $parentLocale;

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
     * Get websites with current locales
     *
     * @return ArrayCollection|Website[]
     */
    public function getWebsites()
    {
        return $this->websites;
    }

    /**
     * Set website for current locales
     *
     * @param ArrayCollection|Locale[] $websites
     *
     * @return Locale
     */
    public function resetWebsites($websites)
    {
        $this->websites->clear();

        foreach ($websites as $website) {
            $this->addWebsite($website);
        }

        return $this;
    }

    /**
     * @param Website $website
     *
     * @return Locale
     */
    public function addWebsite(Website $website)
    {
        if (!$this->websites->contains($website)) {
            $this->websites->add($website);
        }

        return $this;
    }

    /**
     * @param Website $website
     *
     * @return Website
     */
    public function removeWebsite(Website $website)
    {
        if ($this->websites->contains($website)) {
            $this->websites->removeElement($website);
        }

        return $this;
    }
}
