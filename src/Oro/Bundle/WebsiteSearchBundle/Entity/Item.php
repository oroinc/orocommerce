<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Oro\Bundle\SearchBundle\Entity\Item as ORMIndexItem;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *    name="oro_website_search_item",
 *    uniqueConstraints={@ORM\UniqueConstraint(name="oro_website_search_item_uidx", columns={"entity", "record_id"})},
 *    indexes={
 *      @ORM\Index(name="oro_website_search_item_idxe", columns={"alias"}),
 *      @ORM\Index(name="oro_website_search_item_idxa", columns={"entity"})
 *    }
 * )
 * @ORM\Entity
 */
class Item extends ORMIndexItem
{
    const TABLE_NAME = 'oro_website_search_item';
}
