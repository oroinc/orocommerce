<?php

namespace Oro\Bundle\CMSBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentWidgetUsageRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;

/**
 * Holds information about content widget relations.
 */
#[ORM\Entity(repositoryClass: ContentWidgetUsageRepository::class)]
#[ORM\Table(name: 'oro_cms_content_widget_usage')]
#[ORM\UniqueConstraint(
    name: 'uidx_oro_cms_content_widget_usage',
    columns: ['entity_class', 'entity_id', 'entity_field', 'content_widget_id']
)]
#[Config(mode: 'hidden')]
class ContentWidgetUsage
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ContentWidget::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'content_widget_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?ContentWidget $contentWidget = null;

    #[ORM\Column(name: 'entity_class', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $entityClass = null;

    #[ORM\Column(name: 'entity_id', type: Types::INTEGER, nullable: false)]
    protected ?int $entityId = null;

    #[ORM\Column(name: 'entity_field', type: Types::STRING, length: 50, nullable: true)]
    protected ?string $entityField = null;

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

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $entityClass): self
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(int $entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getEntityField(): ?string
    {
        return $this->entityField;
    }

    public function setEntityField(string $entityField): self
    {
        $this->entityField = $entityField;

        return $this;
    }
}
