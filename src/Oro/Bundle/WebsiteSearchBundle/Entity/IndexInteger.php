<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\SearchBundle\Entity\AbstractIndexInteger;

/**
 * Stores values of integer fields
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_website_search_integer')]
#[ORM\Index(columns: ['field'], name: 'oro_website_search_integer_field_idx')]
#[ORM\Index(columns: ['item_id', 'field'], name: 'oro_website_search_integer_item_field_idx')]
class IndexInteger extends AbstractIndexInteger
{
    #[ORM\ManyToOne(targetEntity: Item::class, inversedBy: 'integerFields')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Item $item = null;
}
