<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Oro\Bundle\SearchBundle\Entity\IndexDecimal as ORMIndexDecimal;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_website_search_decimal")
 * @ORM\Entity
 */
class IndexDecimal extends ORMIndexDecimal
{
    const TABLE_NAME = 'oro_website_search_decimal';
}
