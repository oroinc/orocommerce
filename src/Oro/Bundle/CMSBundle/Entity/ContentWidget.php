<?php

namespace Oro\Bundle\CMSBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;
use Oro\Component\Layout\ContextItemInterface;

/**
 * Holds content widget information.
 *
 * @ORM\Entity()
 * @ORM\Table(
 *      name="oro_cms_content_widget",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="uidx_oro_cms_content_widget",
 *              columns={"organization_id","name"}
 *          )
 *      }
 * )
 * @Config(
 *      routeName="oro_cms_content_widget_index",
 *      routeView="oro_cms_content_widget_view",
 *      routeUpdate="oro_cms_content_widget_update",
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *     }
 * )
 */
class ContentWidget implements DatesAwareInterface, OrganizationAwareInterface, ContextItemInterface
{
    use DatesAwareTrait;
    use OrganizationAwareTrait;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="widget_type", type="string", length=255, nullable=false)
     */
    protected $widgetType;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $layout;

    /**
     * @var array
     *
     * @ORM\Column(type = "array")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $settings = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ContentWidget
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return ContentWidget
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getWidgetType(): ?string
    {
        return $this->widgetType;
    }

    /**
     * @param string $widgetType
     * @return ContentWidget
     */
    public function setWidgetType(string $widgetType): self
    {
        $this->widgetType = $widgetType;

        return $this;
    }

    public function getLayout(): ?string
    {
        return $this->layout;
    }

    /**
     * @param string|null $layout
     * @return ContentWidget
     */
    public function setLayout(?string $layout): self
    {
        $this->layout = $layout;

        return $this;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array $settings
     * @return ContentWidget
     */
    public function setSettings(array $settings): self
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return sprintf(
            'name:%s, layout:%s',
            $this->name,
            $this->layout
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getHash(): string
    {
        return md5($this->toString());
    }
}
