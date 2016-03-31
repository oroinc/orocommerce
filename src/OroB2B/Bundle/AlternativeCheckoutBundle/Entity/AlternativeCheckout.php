<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Component\Layout\ContextItemInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface;
use OroB2B\Bundle\AlternativeCheckoutBundle\Model\ExtendAlternativeCheckout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutTrait;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;

/**
 * @ORM\Table(name="orob2b_alternative_checkout")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-shopping-cart"
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id",
 *              "frontend_owner_type"="FRONTEND_USER",
 *              "frontend_owner_field_name"="accountUser",
 *              "frontend_owner_column_name"="account_user_id",
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce"
 *          },
 *          "workflow"={
 *              "active_workflow"="b2b_flow_alternative_checkout"
 *          }
 *      }
 * )
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AlternativeCheckout extends ExtendAlternativeCheckout implements
    CheckoutInterface,
    OrganizationAwareInterface,
    AccountOwnerAwareInterface,
    DatesAwareInterface,
    ContextItemInterface,
    LineItemsNotPricedAwareInterface
{
    const TYPE = 'alternative';
    use CheckoutTrait;

    /**
     * @var bool
     *
     * @ORM\Column(name="allowed", type="boolean")
     */
    protected $allowed;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="allow_request_date", type="datetime", nullable=true)
     */
    protected $allowRequestDate;

    /**
     * @var string
     *
     * @ORM\Column(name="request_approval_notes", type="text", nullable=true)
     */
    protected $requestApprovalNotes;

    /**
     * @return string
     */
    public function getRequestApprovalNotes()
    {
        return $this->requestApprovalNotes;
    }

    /**
     * @param string $requestApprovalNotes
     * @return $this
     */
    public function setRequestApprovalNotes($requestApprovalNotes)
    {
        $this->requestApprovalNotes = $requestApprovalNotes;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isAllowed()
    {
        return $this->allowed;
    }

    /**
     * @param boolean $allowed
     */
    public function setAllowed($allowed)
    {
        $this->allowed = $allowed;
    }

    /**
     * @return \DateTime
     */
    public function getAllowRequestDate()
    {
        return $this->allowRequestDate;
    }

    /**
     * @param \DateTime $allowRequestDate
     */
    public function setAllowRequestDate($allowRequestDate)
    {
        $this->allowRequestDate = $allowRequestDate;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }
}
