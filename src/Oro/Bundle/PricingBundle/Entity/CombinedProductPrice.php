<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *      name="oro_price_product_combined",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="oro_combined_price_uidx",
 *              columns={"product_id", "combined_price_list_id", "quantity", "unit_code", "currency"}
 *          )
 *      },
 *     indexes={
 *         @ORM\Index(
 *              name="oro_combined_price_idx",
 *              columns={
 *                  "combined_price_list_id",
 *                  "product_id",
 *                  "unit_code",
 *                  "quantity",
 *                  "currency"
 *              }
 *         ),
 *         @ORM\Index(
 *              name="oro_cmb_price_mrg_idx",
 *              columns={
 *                  "combined_price_list_id",
 *                  "product_id",
 *                  "merge_allowed"
 *              }
 *         )
 *     }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository")
 */
class CombinedProductPrice extends BaseProductPrice
{
    /**
     * @var PriceList
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PricingBundle\Entity\CombinedPriceList", inversedBy="prices")
     * @ORM\JoinColumn(name="combined_price_list_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     **/
    protected $priceList;

    /**
     * @var boolean
     *
     * @ORM\Column(name="merge_allowed", type="boolean", nullable=false)
     */
    protected $mergeAllowed = true;
}
