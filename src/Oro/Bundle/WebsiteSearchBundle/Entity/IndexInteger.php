<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\SearchBundle\Entity\AbstractIndexInteger;

/**
 * Stores values of integer fields
 *
 * @ORM\Table(
 *      name="oro_website_search_integer",
 *      indexes={
 *          @ORM\Index(name="oro_website_search_integer_field_idx", columns={"field"}),
 *          @ORM\Index(name="oro_website_search_integer_item_field_idx", columns={"item_id", "field"})
 *      }
 * )
 * @ORM\Entity
 */
class IndexInteger extends AbstractIndexInteger
{
    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\WebsiteSearchBundle\Entity\Item", inversedBy="integerFields")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $item;
}
