<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\SearchBundle\Entity\AbstractIndexDecimal;

/**
 * @ORM\Table(name="oro_website_search_decimal")
 * @ORM\Entity
 */
class IndexDecimal extends AbstractIndexDecimal
{
    const TABLE_NAME = 'oro_website_search_decimal';

    /**
     * @ORM\ManyToOne(targetEntity="Item", inversedBy="decimalFields")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $item;
}
