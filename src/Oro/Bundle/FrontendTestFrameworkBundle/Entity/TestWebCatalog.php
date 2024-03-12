<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;

/**
* Entity that represents Test Web Catalog
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_test_web_catalog')]
class TestWebCatalog implements WebCatalogInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
