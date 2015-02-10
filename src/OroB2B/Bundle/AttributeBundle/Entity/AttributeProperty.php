<?php

namespace OroB2B\Bundle\AttributeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Table(
 *      name="orob2b_attribute_property",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="attribute_property_unique_idx",
 *              columns={"attribute_id", "website_id", "field"}
 *          )
 *      }
 * )
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-briefcase"
 *          }
 *      }
 * )
 */
class AttributeProperty
{
    const FIELD_ON_PRODUCT_VIEW       = 'on_product_view';
    const FIELD_IN_PRODUCT_LISTING    = 'in_product_listing';
    const FIELD_USE_IN_SORTING        = 'use_in_sorting';
    const FIELD_USE_FOR_SEARCH        = 'use_for_search';
    const FIELD_ON_ADVANCED_SEARCH    = 'on_advanced_search';
    const FIELD_ON_PRODUCT_COMPARISON = 'on_product_comparison';
    const FIELD_USE_IN_FILTERS        = 'use_in_filters';

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
     * @var string
     *
     * @ORM\Column(name="field", type="string", length=64)
     */
    protected $field;

    /**
     * @var boolean
     *
     * @ORM\Column(name="value", type="boolean", nullable=true)
     */
    protected $value;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    protected $fallback;

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
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param boolean $field
     * @return $this
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isValue()
    {
        return $this->value;
    }

    /**
     * @param boolean $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

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
}
