<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\SearchBundle\Entity\AbstractIndexDatetime;

/**
 * Stores values of datatime fields
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_website_search_datetime')]
#[ORM\Index(columns: ['field'], name: 'oro_website_search_datetime_field_idx')]
#[ORM\Index(columns: ['item_id', 'field'], name: 'oro_website_search_datetime_item_field_idx')]
class IndexDatetime extends AbstractIndexDatetime
{
    #[ORM\ManyToOne(targetEntity: Item::class, inversedBy: 'datetimeFields')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Item $item = null;
}
