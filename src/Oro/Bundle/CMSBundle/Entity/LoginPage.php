<?php

namespace Oro\Bundle\CMSBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroCMSBundle_Entity_LoginPage;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Login page entity class.
 *
 * @method File getLogoImage()
 * @method LoginPage setLogoImage(File $image)
 * @method File getBackgroundImage()
 * @method LoginPage setBackgroundImage(File $image)
 * @mixin OroCMSBundle_Entity_LoginPage
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_cms_login_page')]
#[Config(
    routeName: 'oro_cms_loginpage_index',
    routeUpdate: 'oro_cms_loginpage_update',
    defaultValues: [
        'entity' => ['icon' => 'fa-sign-in'],
        'security' => ['type' => 'ACL', 'group_name' => ''],
        'dataaudit' => ['auditable' => true]
    ]
)]
class LoginPage implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'top_content', type: Types::TEXT, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $topContent = null;

    #[ORM\Column(name: 'bottom_content', type: Types::TEXT, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $bottomContent = null;

    #[ORM\Column(name: 'css', type: Types::TEXT, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $css = null;

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->id;
    }

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
    public function getTopContent()
    {
        return $this->topContent;
    }

    /**
     * @param string|null $topContent
     * @return $this
     */
    public function setTopContent($topContent = null)
    {
        $this->topContent = $topContent;

        return $this;
    }

    /**
     * @return string
     */
    public function getBottomContent()
    {
        return $this->bottomContent;
    }

    /**
     * @param string|null $bottomContent
     * @return $this
     */
    public function setBottomContent($bottomContent = null)
    {
        $this->bottomContent = $bottomContent;

        return $this;
    }

    /**
     * @return string
     */
    public function getCss()
    {
        return $this->css;
    }

    /**
     * @param string|null $css
     * @return $this
     */
    public function setCss($css = null)
    {
        $this->css = $css;

        return $this;
    }
}
