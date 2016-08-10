<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Oro\Bundle\SearchBundle\Entity\Item as ORMIndexItem;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *  name="oro_website_search_item",
 *  uniqueConstraints={@ORM\UniqueConstraint(name="IDX_ENTITY", columns={"entity", "record_id"})},
 *  indexes={@ORM\Index(name="IDX_ALIAS", columns={"alias"}), @ORM\Index(name="IDX_ENTITIES", columns={"entity"})}
 * )
 * @ORM\Entity
 */
class Item extends ORMIndexItem
{
    const TABLE_NAME = 'oro_website_search_item';
}
