<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="oro_test_content_node")
 */
class TestContentNode
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
     * @var TestWebCatalog
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestWebCatalog")
     * @ORM\JoinColumn(name="web_catalog", referencedColumnName="id",nullable=true)
     */
    protected $webCatalog;

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
