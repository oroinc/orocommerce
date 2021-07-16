<?php

namespace Oro\Bundle\CMSBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * Holds information about content widget relations.
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\CMSBundle\Entity\Repository\ContentWidgetUsageRepository")
 * @ORM\Table(name="oro_cms_content_widget_usage", uniqueConstraints={
 *      @ORM\UniqueConstraint(
 *          name="uidx_oro_cms_content_widget_usage",
 *          columns={"entity_class", "entity_id", "entity_field", "content_widget_id"}
 *     )
 * })
 * @Config(
 *      mode="hidden"
 * )
 */
class ContentWidgetUsage
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var ContentWidget
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CMSBundle\Entity\ContentWidget", cascade={"persist"})
     * @ORM\JoinColumn(name="content_widget_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $contentWidget;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_class", type="string", length=255, nullable=false)
     */
    protected $entityClass;

    /**
     * @var int
     *
     * @ORM\Column(name="entity_id", type="integer", nullable=false)
     */
    protected $entityId;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_field", type="string", length=50, nullable=true)
     */
    protected $entityField;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContentWidget(): ?ContentWidget
    {
        return $this->contentWidget;
    }

    /**
     * @param ContentWidget $contentWidget
     * @return ContentWidgetUsage
     */
    public function setContentWidget(ContentWidget $contentWidget): self
    {
        $this->contentWidget = $contentWidget;

        return $this;
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    /**
     * @param string $entityClass
     * @return ContentWidgetUsage
     */
    public function setEntityClass(string $entityClass): self
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    /**
     * @param int $entityId
     * @return ContentWidgetUsage
     */
    public function setEntityId(int $entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getEntityField(): ?string
    {
        return $this->entityField;
    }

    /**
     * @param string $entityField
     * @return ContentWidgetUsage
     */
    public function setEntityField(string $entityField): self
    {
        $this->entityField = $entityField;

        return $this;
    }
}
