<?php

namespace Oro\Bundle\ConsentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ConsentBundle\Model\ExtendConsent;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\UserBundle\Entity\Ownership\AuditableUserAwareTrait;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Entity holds information about consent
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\ConsentBundle\Entity\Repository\ConsentRepository")
 * @ORM\Table(
 *     name="oro_consent",
 *     indexes={@ORM\Index(name="consent_created_idx",columns={"created_at"})}
 * )
 * @Config(
 *      routeName="oro_consent_index",
 *      routeView="oro_consent_view",
 *      routeUpdate="oro_consent_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-check-square"
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id",
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"="account_management"
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class Consent extends ExtendConsent implements
    DatesAwareInterface,
    OrganizationAwareInterface
{
    use DatesAwareTrait;
    use AuditableUserAwareTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_consent_name",
     *      joinColumns={
     *          @ORM\JoinColumn(name="consent_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $names;

    /**
     * @var ContentNode
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\WebCatalogBundle\Entity\ContentNode")
     * @ORM\JoinColumn(name="content_node_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $contentNode;

    /**
     * @var bool
     *
     * @ORM\Column(name="mandatory", type="boolean", nullable=false, options={"default": true})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $mandatory = true;

    /**
     * @var ConsentAcceptance[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ConsentAcceptance", mappedBy="consent", cascade={"persist"})
     */
    protected $acceptances;

    /**
     * @var bool
     *
     * @ORM\Column(name="declined_notification", type="boolean", nullable=false, options={"default": true})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $declinedNotification = true;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->names = new ArrayCollection();
        $this->acceptances = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getNames()
    {
        return $this->names;
    }

    /**
     * @param LocalizedFallbackValue $name
     *
     * @return $this
     */
    public function addName(LocalizedFallbackValue $name)
    {
        if (!$this->names->contains($name)) {
            $this->names->add($name);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $name
     *
     * @return $this
     */
    public function removeName(LocalizedFallbackValue $name)
    {
        if ($this->names->contains($name)) {
            $this->names->removeElement($name);
        }

        return $this;
    }

    /**
     * @return ContentNode
     */
    public function getContentNode()
    {
        return $this->contentNode;
    }

    /**
     * @param ContentNode|null $contentNode
     *
     * @return $this
     */
    public function setContentNode(ContentNode $contentNode = null)
    {
        $this->contentNode = $contentNode;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMandatory()
    {
        return (bool) $this->mandatory;
    }

    /**
     * @param bool $mandatory
     * @return $this
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = $mandatory;

        return $this;
    }

    /**
     * @return bool
     */
    public function getDeclinedNotification()
    {
        return (bool) $this->declinedNotification;
    }

    /**
     * @param bool $declinedNotification
     * @return $this
     */
    public function setDeclinedNotification($declinedNotification)
    {
        $this->declinedNotification = $declinedNotification;

        return $this;
    }

    /**
     * @return ArrayCollection|ConsentAcceptance[]
     */
    public function getAcceptances()
    {
        return $this->acceptances;
    }

    /**
     * @param ConsentAcceptance $acceptance
     *
     * @return Consent
     */
    public function addAcceptance(ConsentAcceptance $acceptance)
    {
        if (!$this->acceptances->contains($acceptance)) {
            $this->acceptances->add($acceptance);
            $acceptance->setConsent($this);
        }

        return $this;
    }

    /**
     * @param ConsentAcceptance $acceptance
     *
     * @return Consent
     */
    public function removeAcceptance(ConsentAcceptance $acceptance)
    {
        if ($this->acceptances->contains($acceptance)) {
            $this->acceptances->removeElement($acceptance);
        }

        return $this;
    }
}
