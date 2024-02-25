<?php

namespace Oro\Bundle\CMSBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;
use Oro\Component\Layout\ContextItemInterface;

/**
 * Holds content widget information.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_cms_content_widget')]
#[ORM\UniqueConstraint(name: 'uidx_oro_cms_content_widget', columns: ['organization_id', 'name'])]
#[Config(
    routeName: 'oro_cms_content_widget_index',
    routeView: 'oro_cms_content_widget_view',
    routeUpdate: 'oro_cms_content_widget_update',
    defaultValues: [
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'organization',
            'owner_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => ''],
        'dataaudit' => ['auditable' => true]
    ]
)]
class ContentWidget implements DatesAwareInterface, OrganizationAwareInterface, ContextItemInterface
{
    use DatesAwareTrait;
    use OrganizationAwareTrait;

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $description = null;

    #[ORM\Column(name: 'widget_type', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $widgetType = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $layout = null;

    /**
     * @var array
     */
    #[ORM\Column(type: Types::ARRAY)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected $settings = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getWidgetType(): ?string
    {
        return $this->widgetType;
    }

    public function setWidgetType(string $widgetType): self
    {
        $this->widgetType = $widgetType;

        return $this;
    }

    public function getLayout(): ?string
    {
        return $this->layout;
    }

    public function setLayout(?string $layout): self
    {
        $this->layout = $layout;

        return $this;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

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
