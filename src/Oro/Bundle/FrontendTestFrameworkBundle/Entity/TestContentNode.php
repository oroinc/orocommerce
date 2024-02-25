<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Test Content Node
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_test_content_node')]
class TestContentNode
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TestWebCatalog::class)]
    #[ORM\JoinColumn(name: 'web_catalog', referencedColumnName: 'id', nullable: true)]
    protected ?TestWebCatalog $webCatalog = null;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return TestWebCatalog
     */
    public function getWebCatalog()
    {
        return $this->webCatalog;
    }

    /**
     * @param TestWebCatalog $webCatalog
     * @return $this
     */
    public function setWebCatalog(TestWebCatalog $webCatalog)
    {
        $this->webCatalog = $webCatalog;

        return $this;
    }
}
