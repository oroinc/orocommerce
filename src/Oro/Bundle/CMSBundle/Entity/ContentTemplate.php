<?php

namespace Oro\Bundle\CMSBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CMSBundle\Model\ExtendContentTemplate;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;

/**
 * ContentTemplate ORM Entity.
 *
 * @ORM\Entity()
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
class ContentTemplate extends ExtendContentTemplate implements DatesAwareInterface, OrganizationAwareInterface
{
    use DatesAwareTrait;
    use UserAwareTrait;

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
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=false
     *          },
     *          "attachment"={
     *              "acl_protected"=false
     *          }
     *      }
     * )
     */
    protected ?string $content = null;

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
