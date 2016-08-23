<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Oro\Bundle\SearchBundle\Entity\IndexInteger as ORMIndexInteger;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_website_search_integer")
 * @ORM\Entity
 */
class IndexInteger extends ORMIndexInteger
{
    const TABLE_NAME = 'oro_website_search_integer';
}
