<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\SearchBundle\Entity\BaseIndexDatetime;

/**
 * @ORM\Table(name="oro_website_search_datetime")
 * @ORM\Entity
 */
class IndexDatetime extends BaseIndexDatetime
{
    const TABLE_NAME = 'oro_website_search_datetime';
}
