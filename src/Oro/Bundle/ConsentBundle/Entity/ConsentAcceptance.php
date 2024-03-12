<?php

namespace Oro\Bundle\ConsentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroConsentBundle_Entity_ConsentAcceptance;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Entity represents accepted consents with certain landing page by CustomerUser
 * CustomerUser relation is added via migration
 *
 *
 *
 * @method CustomerUser getCustomerUser()
 * @method setCustomerUser(CustomerUser $customerUser)
 * @mixin OroConsentBundle_Entity_ConsentAcceptance
 */
#[ORM\Entity(repositoryClass: ConsentAcceptanceRepository::class)]
#[ORM\Table(name: 'oro_consent_acceptance')]
#[ORM\UniqueConstraint(name: 'oro_customeru_consent_uidx', columns: ['consent_id', 'customerUser_id'])]
#[Config]
class ConsentAcceptance implements
    CreatedAtAwareInterface,
    ExtendEntityInterface
{
    use CreatedAtAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Page::class)]
    #[ORM\JoinColumn(name: 'landing_page_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    protected ?Page $landingPage = null;

    #[ORM\ManyToOne(targetEntity: Consent::class, inversedBy: 'acceptances')]
    #[ORM\JoinColumn(name: 'consent_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    protected ?Consent $consent = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Page|null
     */
    public function getLandingPage()
    {
        return $this->landingPage;
    }

    /**
     * @param Page $landingPage
     *
     * @return $this
     */
    public function setLandingPage(Page $landingPage)
    {
        $this->landingPage = $landingPage;

        return $this;
    }

    /**
     * @return Consent
     */
    public function getConsent()
    {
        return $this->consent;
    }

    /**
     * @param Consent $consent
     *
     * @return $this
     */
    public function setConsent(Consent $consent)
    {
        $this->consent = $consent;

        return $this;
    }
}
