<?php

namespace Oro\Bundle\TaxBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;

/**
 * Entity that represents tax code
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository")
 * @ORM\Table(name="oro_tax_product_tax_code", uniqueConstraints={
 *     @ORM\UniqueConstraint(
 *          name="oro_product_tax_code_organization_unique_index",
 *          columns={"code", "organization_id"}
 *     )
 * })
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *      routeName="oro_tax_product_tax_code_index",
 *      routeView="oro_tax_product_tax_code_view",
 *      routeUpdate="oro_tax_product_tax_code_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-list-alt"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          }
 *      }
 * )
 */
class ProductTaxCode extends AbstractTaxCode implements OrganizationAwareInterface
{
    use OrganizationAwareTrait;

    /** {@inheritdoc} */
    public function getType()
    {
        return TaxCodeInterface::TYPE_PRODUCT;
    }
}
