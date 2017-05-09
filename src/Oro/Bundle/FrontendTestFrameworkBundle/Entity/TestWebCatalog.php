<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(name="oro_test_web_catalog")
 */
class TestWebCatalog implements WebCatalogInterface
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
