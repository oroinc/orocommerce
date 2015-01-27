<?php

namespace OroB2B\Bundle\AttributeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Table(name="orob2b_attribute_property")
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class AttributeProperty
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Attribute
     *
     * @ORM\ManyToOne(targetEntity="Attribute", inversedBy="properties")
     * @ORM\JoinColumn(name="attribute_id", referencedColumnName="id", onDelete="CASCADE")
     **/
    protected $attribute;

    /**
     * @var Website
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $website;

    /**
     * @var boolean
     *
     * @ORM\Column(name="on_product_view", type="boolean")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $onProductView = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="in_product_list", type="boolean")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $inProductList = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="use_in_sorting", type="boolean")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $useInSorting = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="use_for_search", type="boolean")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $useForSearch = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="on_advanced_search", type="boolean")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $onAdvancedSearch = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="on_product_comparison", type="boolean")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $onProductComparison = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="in_filters", type="boolean")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $inFilters = false;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $fallback;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime")
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
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return boolean
     */
    public function isOnProductView()
    {
        return $this->onProductView;
    }

    /**
     * @param boolean $onProductView
     * @return $this
     */
    public function setOnProductView($onProductView)
    {
        $this->onProductView = (bool)$onProductView;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isInProductList()
    {
        return $this->inProductList;
    }

    /**
     * @param boolean $inProductList
     * @return $this
     */
    public function setInProductList($inProductList)
    {
        $this->inProductList = $inProductList;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isUseInSorting()
    {
        return $this->useInSorting;
    }

    /**
     * @param boolean $useInSorting
     * @return $this
     */
    public function setUseInSorting($useInSorting)
    {
        $this->useInSorting = (bool)$useInSorting;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isUseForSearch()
    {
        return $this->useForSearch;
    }

    /**
     * @param boolean $useForSearch
     * @return $this
     */
    public function setUseForSearch($useForSearch)
    {
        $this->useForSearch = $useForSearch;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isOnAdvancedSearch()
    {
        return $this->onAdvancedSearch;
    }

    /**
     * @param boolean $onAdvancedSearch
     * @return $this
     */
    public function setOnAdvancedSearch($onAdvancedSearch)
    {
        $this->onAdvancedSearch = (bool)$onAdvancedSearch;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isOnProductComparison()
    {
        return $this->onProductComparison;
    }

    /**
     * @param boolean $onProductComparison
     * @return $this
     */
    public function setOnProductComparison($onProductComparison)
    {
        $this->onProductComparison = (bool)$onProductComparison;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isInFilters()
    {
        return $this->inFilters;
    }

    /**
     * @param boolean $inFilters
     * @return $this
     */
    public function setInFilters($inFilters)
    {
        $this->inFilters = (bool)$inFilters;

        return $this;
    }

    /**
     * @return string
     */
    public function getFallback()
    {
        return $this->fallback;
    }

    /**
     * @param string $fallback
     * @return $this
     */
    public function setFallback($fallback)
    {
        $this->fallback = $fallback;

        return $this;
    }

    /**
     * @return Attribute
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @param Attribute $attribute
     * @return $this
     */
    public function setAttribute(Attribute $attribute)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * @return Website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param Website $website
     * @return $this
     */
    public function setWebsite(Website $website)
    {
        $this->website = $website;

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
}
