<?php

namespace OroB2B\Bundle\UserAdminBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use FOS\UserBundle\Entity\Group as BaseGroup;

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_group")
 */
class Group extends BaseGroup implements Translatable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $name;

    /**
     * @ORM\Column(type="array")
     */
    protected $roles;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     */
    protected $label;

    /**
     * @ORM\OneToMany(
     *     targetEntity="OroB2B\Bundle\UserAdminBundle\Entity\GroupTranslation",
     *     mappedBy="object",
     *     cascade={"persist", "remove"}
     * )
     */
    protected $translations;

    /**
     * @param string $name
     * @param array $roles
     */
    public function __construct($name, $roles = array())
    {
        parent::__construct($name, $roles);
        $this->translations = new ArrayCollection();
    }

    /**
     * Set label
     *
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getLabel();
    }

    /**
     * Set translations
     *
     * @param array|Collection $translations
     *
     * @return $this
     */
    public function setTranslations($translations)
    {
        $this->translations = new ArrayCollection();

        /** @var GroupTranslation $translation */
        foreach ($translations as $translation) {
            $translation->setObject($this);
            $this->translations->add($translation);
        }

        return $this;
    }

    /**
     * Add RequestStatusTranslation
     *
     * @param GroupTranslation $translation
     *
     * @return $this
     */
    public function addTranslation(GroupTranslation $translation)
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setObject($this);
        }

        return $this;
    }

    /**
     * Get translations
     *
     * @return ArrayCollection|GroupTranslation[]
     */
    public function getTranslations()
    {
        return $this->translations;
    }
}
