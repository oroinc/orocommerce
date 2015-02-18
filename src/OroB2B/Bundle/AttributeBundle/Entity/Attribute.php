<?php

namespace OroB2B\Bundle\AttributeBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Table(name="orob2b_attribute")
 * @ORM\Entity
 * @Config(
 *      routeName="orob2b_attribute_index",
 *      routeView="orob2b_attribute_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-briefcase"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *      }
 * )
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Attribute
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
     * @var string
     *
     * @ORM\Column(type="string", length=64)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="sharing_type", type="string", length=64)
     */
    protected $sharingType;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $validation;

    /**
     * @var boolean
     *
     * @ORM\Column(name="contain_html", type="boolean")
     */
    protected $containHtml = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $localized = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $system = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="unique_flag", type="boolean")
     */
    protected $unique = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $required = false;

    /**
     * @var Collection|AttributeProperty[]
     *
     * @ORM\OneToMany(targetEntity="AttributeProperty", mappedBy="attribute", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $properties;

    /**
     * @var Collection|AttributeLabel[]
     *
     * @ORM\OneToMany(targetEntity="AttributeLabel", mappedBy="attribute", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $labels;

    /**
     * @var Collection|AttributeDefaultValue[]
     *
     * @ORM\OneToMany(targetEntity="AttributeDefaultValue", mappedBy="attribute", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $defaultValues;

    /**
     * @var Collection|AttributeOption[]
     *
     * @ORM\OneToMany(targetEntity="AttributeOption", mappedBy="attribute", cascade={"ALL"}, orphanRemoval=true)
     * @ORM\OrderBy({"order" = "ASC"})
     */
    protected $options;

    public function __construct()
    {
        $this->properties = new ArrayCollection();
        $this->labels = new ArrayCollection();
        $this->defaultValues = new ArrayCollection();
        $this->options = new ArrayCollection();
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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
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
     * @return string
     */
    public function getSharingType()
    {
        return $this->sharingType;
    }

    /**
     * @param string $sharingType
     * @return $this
     */
    public function setSharingType($sharingType)
    {
        $this->sharingType = $sharingType;

        return $this;
    }

    /**
     * @return string
     */
    public function getValidation()
    {
        return $this->validation;
    }

    /**
     * @param string $validation
     * @return $this
     */
    public function setValidation($validation)
    {
        $this->validation = $validation;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isContainHtml()
    {
        return $this->containHtml;
    }

    /**
     * @param boolean $containHtml
     * @return $this
     */
    public function setContainHtml($containHtml)
    {
        $this->containHtml = (bool)$containHtml;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isLocalized()
    {
        return $this->localized;
    }

    /**
     * @param boolean $localized
     * @return $this
     */
    public function setLocalized($localized)
    {
        $this->localized = (bool)$localized;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSystem()
    {
        return $this->system;
    }

    /**
     * @param boolean $system
     * @return $this
     */
    public function setSystem($system)
    {
        $this->system = (bool)$system;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isUnique()
    {
        return $this->unique;
    }

    /**
     * @param boolean $unique
     * @return $this
     */
    public function setUnique($unique)
    {
        $this->unique = (bool)$unique;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param boolean $required
     * @return $this
     */
    public function setRequired($required)
    {
        $this->required = (bool)$required;

        return $this;
    }

    /**
     * Get available properties for current attribute
     *
     * @return Collection|AttributeProperty[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param string $field
     * @return Collection|AttributeProperty[]
     */
    public function getPropertiesByField($field)
    {
        return $this->properties->filter(function (AttributeProperty $property) use ($field) {
            return $property->getField() == $field;
        });
    }

    /**
     * @param string $field
     * @param int|null $websiteId
     * @return AttributeProperty|null
     */
    public function getPropertyByFieldAndWebsiteId($field, $websiteId)
    {
        $properties = $this->properties->filter(function (AttributeProperty $property) use ($field, $websiteId) {
            if ($property->getField() != $field) {
                return false;
            }

            $website = $property->getWebsite();
            if ($website) {
                return $website->getId() == $websiteId;
            } else {
                return empty($websiteId);
            }
        });

        return $properties->count() ? $properties->first() : null;
    }

    /**
     * Set properties for current attribute
     *
     * @param Collection|AttributeProperty[] $properties
     *
     * @return $this
     */
    public function resetProperties($properties)
    {
        $this->properties->clear();

        foreach ($properties as $property) {
            $this->addProperty($property);
        }

        return $this;
    }

    /**
     * @param AttributeProperty $property
     *
     * @return $this
     */
    public function addProperty(AttributeProperty $property)
    {
        if (!$this->properties->contains($property)) {
            $this->properties->add($property);
            $property->setAttribute($this);
        }

        return $this;
    }

    /**
     * @param AttributeProperty $property
     *
     * @return $this
     */
    public function removeProperty(AttributeProperty $property)
    {
        if ($this->properties->contains($property)) {
            $this->properties->removeElement($property);
        }

        return $this;
    }

    /**
     * Get available labels for current attribute
     *
     * @return Collection|AttributeLabel[]
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @param int|null $localeId
     * @return AttributeLabel|null
     */
    public function getLabelByLocaleId($localeId)
    {
        $labels = $this->labels->filter(function (AttributeLabel $label) use ($localeId) {
            $locale = $label->getLocale();
            if ($locale) {
                return $locale->getId() == $localeId;
            } else {
                return empty($localeId);
            }
        });

        return $labels->count() ? $labels->first() : null;
    }

    /**
     * Set labels for current attribute
     *
     * @param Collection|AttributeLabel[] $labels
     *
     * @return $this
     */
    public function resetLabels($labels)
    {
        $this->labels->clear();

        foreach ($labels as $label) {
            $this->addLabel($label);
        }

        return $this;
    }

    /**
     * @param AttributeLabel $label
     *
     * @return $this
     */
    public function addLabel(AttributeLabel $label)
    {
        if (!$this->labels->contains($label)) {
            $this->labels->add($label);
            $label->setAttribute($this);
        }

        return $this;
    }

    /**
     * @param AttributeLabel $label
     *
     * @return $this
     */
    public function removeLabel(AttributeLabel $label)
    {
        if ($this->labels->contains($label)) {
            $this->labels->removeElement($label);
        }

        return $this;
    }

    /**
     * Get available default values for current attribute
     *
     * @return Collection|AttributeDefaultValue[]
     */
    public function getDefaultValues()
    {
        return $this->defaultValues;
    }

    /**
     * @param int|null $localeId
     * @return AttributeDefaultValue[]|Collection
     */
    public function getDefaultValuesByLocaleId($localeId)
    {
        return $this->defaultValues->filter(function (AttributeDefaultValue $value) use ($localeId) {
            $locale = $value->getLocale();
            if ($locale) {
                return $locale->getId() == $localeId;
            } else {
                return empty($localeId);
            }
        });
    }

    /**
     * @param int|null $localeId
     * @return AttributeDefaultValue|null
     */
    public function getDefaultValueByLocaleId($localeId)
    {
        $values = $this->getDefaultValuesByLocaleId($localeId);

        return $values->count() ? $values->first() : null;
    }

    /**
     * @param int|null $localeId
     * @param int $optionId
     * @return AttributeDefaultValue|null
     */
    public function getDefaultValueByLocaleIdAndOptionId($localeId, $optionId)
    {
        $values = $this->getDefaultValuesByLocaleId($localeId);

        $values = $values->filter(function (AttributeDefaultValue $value) use ($optionId) {
            $option = $value->getOption();
            if (!$option || !$option->getId()) {
                return false;
            }

            return $option->getId() == $optionId;
        });

        return $values->count() ? $values->first() : null;
    }


    /**
     * Set default values for current attribute
     *
     * @param Collection|AttributeDefaultValue[] $values
     *
     * @return $this
     */
    public function resetDefaultValues($values)
    {
        $this->defaultValues->clear();

        foreach ($values as $value) {
            $this->addDefaultValue($value);
        }

        return $this;
    }

    /**
     * @param AttributeDefaultValue $value
     *
     * @return $this
     */
    public function addDefaultValue(AttributeDefaultValue $value)
    {
        if (!$this->defaultValues->contains($value)) {
            $this->defaultValues->add($value);
            $value->setAttribute($this);
        }

        return $this;
    }

    /**
     * @param AttributeDefaultValue $value
     *
     * @return $this
     */
    public function removeDefaultValue(AttributeDefaultValue $value)
    {
        if ($this->defaultValues->contains($value)) {
            $this->defaultValues->removeElement($value);
        }

        return $this;
    }

    /**
     * Get available options for current attribute
     *
     * @return Collection|AttributeOption[]
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param int $optionId
     * @return AttributeOption|null
     */
    public function getOptionById($optionId)
    {
        $options = $this->options->filter(function (AttributeOption $option) use ($optionId) {
            $currentOptionId = $option->getId();
            if (!$currentOptionId) {
                return false;
            }

            return $currentOptionId == $optionId;
        });

        return $options->count() ? $options->first() : null;
    }

    /**
     * Set options for current attribute
     *
     * @param Collection|AttributeOption[] $options
     *
     * @return $this
     */
    public function resetOptions($options)
    {
        $this->options->clear();

        foreach ($options as $option) {
            $this->addOption($option);
        }

        return $this;
    }

    /**
     * @param AttributeOption $option
     *
     * @return $this
     */
    public function addOption(AttributeOption $option)
    {
        if (!$this->options->contains($option)) {
            $this->options->add($option);
            $option->setAttribute($this);
        }

        return $this;
    }

    /**
     * @param AttributeOption $option
     *
     * @return $this
     */
    public function removeOption(AttributeOption $option)
    {
        if ($this->options->contains($option)) {
            $this->options->removeElement($option);
        }

        return $this;
    }
}
