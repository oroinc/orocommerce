<?php

namespace Oro\Bundle\CMSBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;

/**
 * Holds tabbed content item data.
 *
 * @ORM\Entity()
 * @ORM\Table(name="oro_cms_tabbed_content_item")
 * @Config(
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
class TabbedContentItem implements
    OrganizationAwareInterface,
    DatesAwareInterface,
    ExtendEntityInterface
{
    use OrganizationAwareTrait;
    use DatesAwareTrait;
    use ExtendEntityTrait;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CMSBundle\Entity\ContentWidget")
     * @ORM\JoinColumn(name="content_widget_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected ?ContentWidget $contentWidget = null;

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected ?string $title = null;

    /**
     * @ORM\Column(name="item_order", type="integer", options={"default"=0})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected ?int $itemOrder = 0;

    /**
     * @ORM\Column(name="content", type="wysiwyg", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "attachment"={
     *              "acl_protected"=false
     *          }
     *      }
     * )
     */
    protected ?string $content = null;

    /**
     * @ORM\Column(name="content_style", type="wysiwyg_style", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "attachment"={
     *              "acl_protected"=false
     *          }
     *      }
     * )
     */
    protected ?string $contentStyle = null;

    /**
     * @ORM\Column(name="content_properties", type="wysiwyg_properties", nullable=true)
     */
    protected ?array $contentProperties = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContentWidget(): ?ContentWidget
    {
        return $this->contentWidget;
    }

    public function setContentWidget(ContentWidget $contentWidget): self
    {
        $this->contentWidget = $contentWidget;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getItemOrder(): ?int
    {
        return $this->itemOrder;
    }

    public function setItemOrder(?int $itemOrder): self
    {
        $this->itemOrder = $itemOrder;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getContentStyle(): ?string
    {
        return $this->contentStyle;
    }

    public function setContentStyle(?string $contentStyle): self
    {
        $this->contentStyle = $contentStyle;

        return $this;
    }

    public function getContentProperties(): ?array
    {
        return $this->contentProperties;
    }

    public function setContentProperties(?array $contentProperties): self
    {
        $this->contentProperties = $contentProperties;

        return $this;
    }
}
