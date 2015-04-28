<?php

namespace OroB2B\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * EmailTemplate
 *
 * @ORM\Table(
 *      name="oro_email_template",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="UQ_NAME", columns={"name", "entityName"})
 *      },
 *      indexes={
 *          @ORM\Index(name="email_name_idx", columns={"name"}),
 *          @ORM\Index(name="email_is_system_idx", columns={"isSystem"}),
 *          @ORM\Index(name="email_entity_name_idx", columns={"entityName"})
 *      }
 * )
 * @ORM\Entity
 * @Gedmo\TranslationEntity(class="Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation")
 */
class EmailTemplate implements Translatable
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isSystem", type="boolean")
     */
    protected $isSystem;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isEditable", type="boolean")
     */
    protected $isEditable;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent", type="integer", nullable=true)
     */
    protected $parent;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     */
    protected $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     * @Gedmo\Translatable
     */
    protected $content;

    /**
     * @var string
     *
     * @ORM\Column(name="entityName", type="string", length=255, nullable=true)
     */
    protected $entityName;

    /**
     * Template type:
     *  - html
     *  - text
     *
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20)
     */
    protected $type;

    /**
     * @var string
     *
     * @Gedmo\Locale()
     */
    protected $locale;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="OroB2B\Bundle\EmailBundle\Entity\EmailTemplateTranslation",
     *     mappedBy="object",
     *     cascade={"persist", "remove"}
     * )
     * @Assert\Valid(deep = true)
     */
    protected $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return EmailTemplate
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set parent
     *
     * @param integer $parent
     * @return EmailTemplate
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return integer
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return EmailTemplate
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return EmailTemplate
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set entityName
     *
     * @param string $entityName
     * @return EmailTemplate
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;

        return $this;
    }

    /**
     * Get entityName
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Set a flag indicates whether a template is system or not.
     *
     * @param boolean $isSystem
     * @return EmailTemplate
     */
    public function setIsSystem($isSystem)
    {
        $this->isSystem = $isSystem;

        return $this;
    }

    /**
     * Get a flag indicates whether a template is system or not.
     * System templates cannot be removed or changed.
     *
     * @return boolean
     */
    public function getIsSystem()
    {
        return $this->isSystem;
    }

    /**
     * Get a flag indicates whether a template can be changed.
     *
     * @param boolean $isEditable
     * @return EmailTemplate
     */
    public function setIsEditable($isEditable)
    {
        $this->isEditable = $isEditable;

        return $this;
    }

    /**
     * Get a flag indicates whether a template can be changed.
     * For user's templates this flag has no sense (these templates always have this flag true)
     * But editable system templates can be changed (but cannot be removed or renamed).
     *
     * @return boolean
     */
    public function getIsEditable()
    {
        return $this->isEditable;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return EmailTemplate
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set template type
     *
     * @param string $type
     * @return EmailTemplate
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get template type
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set translations
     *
     * @param ArrayCollection $translations
     * @return EmailTemplate
     */
    public function setTranslations($translations)
    {
        /** @var EmailTemplateTranslation $translation */
        foreach ($translations as $translation) {
            $translation->setObject($this);
        }

        $this->translations = $translations;

        return $this;
    }

    /**
     * Add translation
     *
     * @param EmailTemplateTranslation $translation
     * @return $this
     */
    public function addTranslation(EmailTemplateTranslation $translation)
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
     * @return ArrayCollection|EmailTemplateTranslation[]
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Convert entity to string
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getName();
    }
}
