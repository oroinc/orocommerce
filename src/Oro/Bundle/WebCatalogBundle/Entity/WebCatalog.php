<?php

namespace Oro\Bundle\WebCatalogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\BusinessUnitAwareTrait;
use Oro\Bundle\WebCatalogBundle\Model\ExtendWebCatalog;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="oro_web_catalog")
 * @Config(
 *      routeName="oro_web_catalog_index",
 *      routeView="oro_web_catalog_view",
 *      routeUpdate="oro_web_catalog_update",
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="BUSINESS_UNIT",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="business_unit_owner_id"
 *          }
 *     }
 * )
 */
class WebCatalog extends ExtendWebCatalog implements WebCatalogInterface, DatesAwareInterface
{
    use BusinessUnitAwareTrait;
    use DatesAwareTrait;

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
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

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
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return WebCatalog
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
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
     * @return WebCatalog
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
