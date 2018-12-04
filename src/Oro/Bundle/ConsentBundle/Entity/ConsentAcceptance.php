<?php

namespace Oro\Bundle\ConsentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareTrait;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository")
 *
 * @ORM\Table(
 *     name="oro_consent_acceptance",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="oro_customer_consent_uidx",
 *              columns={"consent_id","customer_user_id"}
 *          )
 *      }
 * )
 */
class ConsentAcceptance implements CreatedAtAwareInterface
{
    use CreatedAtAwareTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var CustomerUser
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerUser")
     * @ORM\JoinColumn(name="customer_user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $customerUser;

    /**
     * @var Page
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CMSBundle\Entity\Page")
     * @ORM\JoinColumn(name="landing_page_id", referencedColumnName="id", nullable=true, onDelete="RESTRICT")
     */
    protected $landingPage;

    /**
     * @var Consent
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ConsentBundle\Entity\Consent", inversedBy="acceptances")
     * @ORM\JoinColumn(name="consent_id", referencedColumnName="id", nullable=false, onDelete="RESTRICT")
     */
    protected $consent;

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

    /**
     * @return CustomerUser
     */
    public function getCustomerUser()
    {
        return $this->customerUser;
    }

    /**
     * @param CustomerUser $customerUser
     *
     * @return $this
     */
    public function setCustomerUser(CustomerUser $customerUser)
    {
        $this->customerUser = $customerUser;

        return $this;
    }
}
