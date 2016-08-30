<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\SearchBundle\Entity\AbstractIndexText;

/**
 * @ORM\Table(name="oro_website_search_text")
 * @ORM\Entity
 */
class IndexText extends AbstractIndexText
{
    const TABLE_NAME = 'oro_website_search_text';
}
