<?php

namespace OroB2B\Bundle\RFPAdminBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\RFPBundle\Entity\AbstractRequestStatus;

/**
 * RequestStatus
 *
 * @ORM\Table(
 *      name="orob2b_rfp_status",
 *      indexes={
 *          @ORM\Index(name="orob2b_rfp_status_name_idx",columns={"name"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\RFPAdminBundle\Entity\Repository\RequestStatusRepository")
 * @Gedmo\TranslationEntity(class="OroB2B\Bundle\RFPAdminBundle\Entity\RequestStatusTranslation")
 * @Config(
 *      routeName="orob2b_rfp_admin_request_status_index",
 *      routeView="orob2b_rfp_admin_request_status_view",
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
class RequestStatus extends AbstractRequestStatus implements Translatable
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
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     */
    protected $label;

    /**
     * @ORM\OneToMany(
     *     targetEntity="OroB2B\Bundle\RFPAdminBundle\Entity\RequestStatusTranslation",
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
    protected $sortOrder;

    /**
     * @var boolean
     *
     * @ORM\Column(name="deleted", type="boolean", options={"default"=false})
     */
    protected $deleted = false;

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
            $this->translations->add($translation);
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
