<?php

namespace Oro\Bundle\WebCatalogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\WebCatalogBundle\Model\ExtendWebCatalogNode;
use Oro\Component\WebCatalog\Entity\WebCatalogNodeInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="oro_web_catalog_node")
 * @Config
 */
class WebCatalogNode extends ExtendWebCatalogNode implements WebCatalogNodeInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return WebCatalogNode
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
