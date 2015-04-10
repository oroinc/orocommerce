<?php

namespace OroB2B\Bundle\RFPBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * RequestStatus
 *
 * @ORM\Table("orob2b_rfp_status")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\RFPBundle\Entity\Repository\RequestStatusRepository")
 * @Gedmo\TranslationEntity(class="OroB2B\Bundle\RFPBundle\Entity\RequestStatusTranslation")
 * @Config(
 *      routeName="orob2b_rfp_request_status_index",
 *      routeView="orob2b_rfp_request_status_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-file-text"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class RequestStatus implements Translatable
{
    const OPEN = 'open';
    const CLOSED = 'closed';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     */
    private $label;

    /**
     * @ORM\OneToMany(
     *     targetEntity="OroB2B\Bundle\RFPBundle\Entity\RequestStatusTranslation",
     *     mappedBy="object",
     *     cascade={"persist", "remove"}
     * )
     * @Assert\Valid(deep = true)
     */
    protected $translations;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort_order", type="integer", nullable=true)
     */
    private $sortOrder;

    /**
     * @var boolean
     *
     * @ORM\Column(name="deleted", type="boolean", options={"default"=false})
     */
    private $deleted = false;

    /**
     * @var string
     *
     * @Gedmo\Locale()
     */
    protected $locale;

    /**
     * Constructor
     */
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
     * @return RequestStatus
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
     * Set label
     *
     * @param string $label
     * @return RequestStatus
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
     * Set sortOrder
     *
     * @param integer $sortOrder
     * @return RequestStatus
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * Get sortOrder
     *
     * @return integer
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * Set deleted
     *
     * @param boolean $deleted
     * @return RequestStatus
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted
     *
     * @return boolean
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set locale
     *
     * @param string $locale
     *
     * @return RequestStatus
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
     * @return RequestStatus
     */
    public function setTranslations($translations)
    {
        $this->translations = new ArrayCollection();

        /** @var RequestStatusTranslation $translation */
        foreach ($translations as $translation) {
            $translation->setObject($this);
            $this->translations->add($translation);
        }

        return $this;
    }

    /**
     * Add RequestStatusTranslation
     *
     * @param RequestStatusTranslation $translation
     *
     * @return $this
     */
    public function addTranslation(RequestStatusTranslation $translation)
    {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->setObject($this);
        }

        return $this;
    }

    /**
     * Get translations
     *
     * @return ArrayCollection|RequestStatusTranslation[]
     */
    public function getTranslations()
    {
        return $this->translations;
    }
}
