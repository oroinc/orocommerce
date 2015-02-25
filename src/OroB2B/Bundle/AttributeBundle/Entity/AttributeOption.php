<?php

namespace OroB2B\Bundle\AttributeBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

/**
 * @ORM\Table(name="orob2b_attribute_option")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\AttributeBundle\Entity\Repository\AttributeOptionRepository")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-briefcase"
 *          }
 *      }
 * )
 */
class AttributeOption
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
     * @ORM\ManyToOne(targetEntity="Attribute", inversedBy="options")
     * @ORM\JoinColumn(name="attribute_id", referencedColumnName="id", onDelete="CASCADE")
     **/
    protected $attribute;

    /**
     * @var Locale
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\WebsiteBundle\Entity\Locale")
     * @ORM\JoinColumn(name="locale_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $locale;

    /**
     * @var AttributeOption
     *
     * @ORM\ManyToOne(targetEntity="AttributeOption", inversedBy="relatedOptions")
     * @ORM\JoinColumn(name="master_option_id", referencedColumnName="id", onDelete="CASCADE")
     **/
    protected $masterOption;

    /**
     * @ORM\OneToMany(targetEntity="AttributeOption", mappedBy="masterOption")
     **/
    protected $relatedOptions;

    /**
     * @var string $value
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $value;

    /**
     * @var int $order
     *
     * @ORM\Column(name="order_value", type="integer")
     */
    protected $order;

    /**
     * @var string $fallback
     *
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    protected $fallback;

    public function __construct()
    {
        $this->relatedOptions = new ArrayCollection();
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
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param int $order
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return Locale|null
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param Locale|null $locale
     * @return $this
     */
    public function setLocale(Locale $locale = null)
    {
        $this->locale = $locale;

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
     * @return AttributeOption|null
     */
    public function getMasterOption()
    {
        return $this->masterOption;
    }

    /**
     * @param AttributeOption|null $option
     * @return $this
     */
    public function setMasterOption(AttributeOption $option = null)
    {
        $this->masterOption = $option;
        if ($option) {
            $this->attribute = $option->getAttribute();
        }

        return $this;
    }

    /**
     * @return Collection|AttributeOption[]
     */
    public function getRelatedOptions()
    {
        return $this->relatedOptions;
    }

    /**
     * @param int|null $localeId
     * @return AttributeOption|null
     */
    public function getRelatedOptionByLocaleId($localeId)
    {
        $options = $this->relatedOptions->filter(function (AttributeOption $option) use ($localeId) {
            $locale = $option->getLocale();
            if ($locale) {
                return $locale->getId() == $localeId;
            } else {
                return empty($localeId);
            }
        });

        $count = $options->count();
        if ($count > 1) {
            throw new \LogicException('Several related attribute options found by the same locale ID.');
        }

        return $count > 0 ? $options->first() : null;
    }

    /**
     * @param AttributeOption $option
     * @return $this
     */
    public function addRelatedOption(AttributeOption $option)
    {
        if (!$this->relatedOptions->contains($option)) {
            $this->relatedOptions->add($option);
            $option->setMasterOption($this);
        }

        return $this;
    }

    /**
     * @param AttributeOption $option
     * @return $this
     */
    public function removeRelatedOption(AttributeOption $option)
    {
        if ($this->relatedOptions->contains($option)) {
            $this->relatedOptions->removeElement($option);
        }

        return $this;
    }
}
