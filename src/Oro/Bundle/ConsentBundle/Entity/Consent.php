<?php

namespace Oro\Bundle\ConsentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroConsentBundle_Entity_Consent;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentRepository;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\UserBundle\Entity\Ownership\AuditableUserAwareTrait;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Entity holds information about consent
 *
 *
 * @method LocalizedFallbackValue getName(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultName()
 * @method setDefaultName(string $value)
 * @mixin OroConsentBundle_Entity_Consent
 */
#[ORM\Entity(repositoryClass: ConsentRepository::class)]
#[ORM\Table(name: 'oro_consent')]
#[ORM\Index(columns: ['created_at'], name: 'consent_created_idx')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    routeName: 'oro_consent_index',
    routeView: 'oro_consent_view',
    routeUpdate: 'oro_consent_update',
    defaultValues: [
        'entity' => ['icon' => 'fa-check-square'],
        'ownership' => [
            'owner_type' => 'USER',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'user_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'dataaudit' => ['auditable' => true],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'account_management']
    ]
)]
class Consent implements
    DatesAwareInterface,
    OrganizationAwareInterface,
    ExtendEntityInterface
{
    use DatesAwareTrait;
    use AuditableUserAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'oro_consent_name')]
    #[ORM\JoinColumn(name: 'consent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Collection $names = null;

    #[ORM\ManyToOne(targetEntity: ContentNode::class, inversedBy: 'referencedConsents')]
    #[ORM\JoinColumn(name: 'content_node_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?ContentNode $contentNode = null;

    #[ORM\Column(name: 'mandatory', type: Types::BOOLEAN, nullable: false, options: ['default' => true])]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?bool $mandatory = true;

    /**
     * @var Collection<int, ConsentAcceptance>
     */
    #[ORM\OneToMany(mappedBy: 'consent', targetEntity: ConsentAcceptance::class, cascade: ['persist'])]
    protected ?Collection $acceptances = null;

    #[ORM\Column(name: 'declined_notification', type: Types::BOOLEAN, nullable: false, options: ['default' => true])]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?bool $declinedNotification = true;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
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
