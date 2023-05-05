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
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;

/**
 * ContentTemplate ORM Entity.
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\CMSBundle\Entity\Repository\ContentTemplateRepository")
 * @ORM\Table(name="oro_cms_content_template")
 * @Config(
 *      routeName="oro_cms_content_template_index",
 *      routeView="oro_cms_content_template_view",
 *      routeUpdate="oro_cms_content_template_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-code"
 *          },
 *          "dataaudit"={
 *              "auditable"=false
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "tag"={
 *              "enabled"=true
 *          },
 *      }
 * )
 */
class ContentTemplate implements DatesAwareInterface, OrganizationAwareInterface, ExtendEntityInterface
{
    use DatesAwareTrait;
    use UserAwareTrait;
    use ExtendEntityTrait;

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected ?string $name = null;

    /**
     * @ORM\Column(name="content", type="wysiwyg", nullable=true)
     */
    protected ?string $content = null;

    /**
     * @ORM\Column(type="wysiwyg_style", name="content_style", nullable=true)
     * @ConfigField(mode="hidden")
     */
    protected ?string $contentStyle = null;

    /**
     * @ORM\Column(type="wysiwyg_properties", name="content_properties", nullable=true)
     * @ConfigField(mode="hidden")
     */
    protected ?array $contentProperties = null;

    /**
     * @ORM\Column(name="enabled", type="boolean", options={"default": true})
     */
    protected bool $enabled = true;

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

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }
}
