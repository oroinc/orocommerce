<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\SearchBundle\Entity\BaseIndexDecimal;

/**
 * @ORM\Table(name="oro_website_search_decimal")
 * @ORM\Entity
 */
class IndexDecimal extends BaseIndexDecimal
{
    const TABLE_NAME = 'oro_website_search_decimal';
}
