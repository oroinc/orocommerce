<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\SearchBundle\Entity\AbstractIndexDatetime;

/**
 * @ORM\Table(name="oro_website_search_datetime")
 * @ORM\Entity
 */
class IndexDatetime extends AbstractIndexDatetime
{
    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\WebsiteSearchBundle\Entity\Item", inversedBy="datetimeFields")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $item;
}
