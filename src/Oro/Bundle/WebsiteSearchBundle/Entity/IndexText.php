<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\SearchBundle\Entity\BaseIndexText;

/**
 * @ORM\Table(name="oro_website_search_text")
 * @ORM\Entity
 */
class IndexText extends BaseIndexText
{
    const TABLE_NAME = 'oro_website_search_text';
}
