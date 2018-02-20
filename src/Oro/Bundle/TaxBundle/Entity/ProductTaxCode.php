<?php

namespace Oro\Bundle\TaxBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository")
 * @ORM\Table(name="oro_tax_product_tax_code")
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
 *          }
 *      }
 * )
 */
class ProductTaxCode extends AbstractTaxCode
{
    /** {@inheritdoc} */
    public function getType()
    {
        return TaxCodeInterface::TYPE_PRODUCT;
    }
}
