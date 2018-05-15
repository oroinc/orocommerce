<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\SearchBundle\Entity\AbstractIndexText;

/**
 * Stores values of fields of the text type
 *
 * @ORM\Table(
 *      name="oro_website_search_text",
 *      indexes={
 *          @ORM\Index(name="oro_website_search_text_field_idx", columns={"field"}),
 *          @ORM\Index(name="oro_website_search_text_item_field_idx", columns={"item_id", "field"})
 *      }
 * )
 * @ORM\Entity
 */
class IndexText extends AbstractIndexText
{
}
