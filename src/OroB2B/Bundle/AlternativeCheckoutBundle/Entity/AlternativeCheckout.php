<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutAddressesTrait;

/**
 * @ORM\Table(name="orob2b_alternative_checkout")
 * @ORM\Entity(
 *     repositoryClass="OroB2B\Bundle\AlternativeCheckoutBundle\Entity\Repository\AlternativeCheckoutRepository"
 * )
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-shopping-cart"
 *          },
 *          "ownership"={
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
 */
class AlternativeCheckout extends BaseCheckout
{
    use CheckoutAddressesTrait;

    const CHECKOUT_TYPE = 'alternative';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var bool
     *
     * @ORM\Column(name="allowed", type="boolean")
     */
    protected $allowed = false;

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
     * @var bool
     *
     * @ORM\Column(name="requested_for_approve", type="boolean")
     */
    protected $requestedForApprove = false;

    public function __construct()
    {
        $this->checkoutType = self::CHECKOUT_TYPE;
    }

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
    public function isRequestedForApprove()
    {
        return $this->requestedForApprove;
    }

    /**
     * @param boolean $requestedForApprove
     */
    public function setRequestedForApprove($requestedForApprove)
    {
        $this->requestedForApprove = $requestedForApprove;
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
}
