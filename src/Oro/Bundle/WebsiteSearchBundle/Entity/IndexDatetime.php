<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Oro\Bundle\SearchBundle\Entity\IndexDatetime as ORMIndexDateTime;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_website_search_datetime")
 * @ORM\Entity
 */
class IndexDatetime extends ORMIndexDateTime
{
    const TABLE_NAME = 'oro_website_search_idx_datetime';
}
