<?php

namespace OroB2B\Bundle\WebsiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Table(name="orob2b_attribute")
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-briefcase"
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
     * @ORM\Column(type="string", length=64, unique=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $type;

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
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="sharing_type", type="string", length=64, unique=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $sharingType;

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
    protected $validation;

    /**
     * @var boolean
     *
     * @ORM\Column(name="contain_html", type="boolean")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $containHtml = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $localized = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $system = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $unique = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $required = false;

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
     * @var Collection|AttributeProperty[]
     *
     * @ORM\OneToMany(targetEntity="AttributeProperty", mappedBy="attribute")
     */
    protected $properties;

    /**
     * @var Collection|AttributeLabel[]
     *
     * @ORM\OneToMany(targetEntity="AttributeLabel", mappedBy="attribute")
     */
    protected $labels;

    /**
     * @var Collection|AttributeDefaultValue[]
     *
     * @ORM\OneToMany(targetEntity="AttributeDefaultValue", mappedBy="attribute")
     */
    protected $defaultValues;

    /**
     * @var Collection|AttributeOption[]
     *
     * @ORM\OneToMany(targetEntity="AttributeOption", mappedBy="attribute")
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
        return $this->properties;
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
        return $this->defaultValues;
    }

    /**
     * Set options for current attribute
     *
     * @param Collection|AttributeOption[] $options
     *
     * @return $this
     */
    public function resetAttributeOptions($options)
    {
        $this->defaultValues->clear();

        foreach ($options as $option) {
            $this->addDefaultValue($option);
        }

        return $this;
    }

    /**
     * @param AttributeOption $option
     *
     * @return $this
     */
    public function addAttributeOption(AttributeOption $option)
    {
        if (!$this->options->contains($option)) {
            $this->options->add($option);
        }

        return $this;
    }

    /**
     * @param AttributeOption $option
     *
     * @return $this
     */
    public function removeAttributeOption(AttributeOption $option)
    {
        if ($this->options->contains($option)) {
            $this->options->removeElement($option);
        }

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
