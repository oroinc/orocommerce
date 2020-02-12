<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity to store prices combined from price list chain by merge strategy.
 *
 * @ORM\Table(
 *      name="oro_price_product_combined",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="oro_combined_price_unq_idx",
 *              columns={"combined_price_list_id", "product_id", "currency", "unit_code", "quantity"}
 *          )
 *      },
 *     indexes={
 *         @ORM\Index(
 *              name="oro_cmb_price_mrg_idx",
 *              columns={
 *                  "combined_price_list_id",
 *                  "product_id",
 *                  "merge_allowed"
 *              }
 *         ),
 *         @ORM\Index(
 *              name="oro_cmb_price_product_currency_idx",
 *              columns={
 *                  "product_id",
 *                  "currency"
 *              }
 *         )
 *     }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository")
 * @method CombinedPriceList getPriceList()
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
