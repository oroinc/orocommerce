<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\SearchBundle\Entity\AbstractIndexInteger;

/**
 * @ORM\Table(name="oro_website_search_integer")
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
