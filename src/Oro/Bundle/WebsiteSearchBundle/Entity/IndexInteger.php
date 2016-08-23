<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\SearchBundle\Entity\BaseIndexInteger;

/**
 * @ORM\Table(name="oro_website_search_integer")
 * @ORM\Entity
 */
class IndexInteger extends BaseIndexInteger
{
    const TABLE_NAME = 'oro_website_search_integer';
}
