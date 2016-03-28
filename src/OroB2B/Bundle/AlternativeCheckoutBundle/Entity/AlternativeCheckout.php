<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface;
use Oro\Component\Layout\ContextItemInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface;
use OroB2B\Bundle\AlternativeCheckoutBundle\Model\ExtendAlternativeCheckout;
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
    OrganizationAwareInterface,
    AccountOwnerAwareInterface,
    DatesAwareInterface,
    WorkflowAwareInterface,
    ContextItemInterface,
    LineItemsNotPricedAwareInterface
{
    use CheckoutTrait;
}
