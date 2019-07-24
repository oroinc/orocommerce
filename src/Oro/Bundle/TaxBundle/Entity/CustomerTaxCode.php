<?php

namespace Oro\Bundle\TaxBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;

/**
 * Entity that represents tax code
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\TaxBundle\Entity\Repository\CustomerTaxCodeRepository")
 * @ORM\Table(name="oro_tax_customer_tax_code", uniqueConstraints={
 *     @ORM\UniqueConstraint(
 *          name="oro_customer_tax_code_organization_unique_index",
 *          columns={"code", "organization_id"}
 *     )
 * })
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *      routeName="oro_tax_customer_tax_code_index",
 *      routeView="oro_tax_customer_tax_code_view",
 *      routeUpdate="oro_tax_customer_tax_code_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-list-alt"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce"
 *          }
 *      }
 * )
 */
class CustomerTaxCode extends AbstractTaxCode implements OrganizationAwareInterface
{
    use UserAwareTrait;

    /** {@inheritdoc} */
    public function getType()
    {
        return TaxCodeInterface::TYPE_ACCOUNT;
    }
}
