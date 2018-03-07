<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\SearchBundle\Entity\AbstractIndexDecimal;

/**
 * Stores values of decimal fields
 *
 * @ORM\Table(
 *      name="oro_website_search_decimal",
 *      indexes={
 *          @ORM\Index(name="oro_website_search_decimal_field_idx", columns={"field"}),
 *          @ORM\Index(name="oro_website_search_decimal_item_field_idx", columns={"item_id", "field"})
 *      }
 * )
 * @ORM\Entity
 */
class IndexDecimal extends AbstractIndexDecimal
{
    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\WebsiteSearchBundle\Entity\Item", inversedBy="decimalFields")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $item;
}
