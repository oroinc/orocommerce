<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Oro\Bundle\SearchBundle\Entity\IndexText as ORMIndexText;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_website_search_text")
 * @ORM\Entity
 */
class IndexText extends ORMIndexText
{
    const TABLE_NAME = 'oro_website_search_idx_text';
}
