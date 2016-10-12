<?php

namespace Oro\Bundle\WebCatalogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\WebCatalogBundle\Model\ExtendWebCatalogPage;
use Oro\Component\WebCatalog\Entity\WebCatalogPageInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="oro_web_catalog_page")
 * @Config
 */
class WebCatalogPage extends ExtendWebCatalogPage implements WebCatalogPageInterface
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
     * @ORM\Column(name="type", type="string", length=255)
     */
    protected $type;

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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return WebCatalogPage
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }
}
