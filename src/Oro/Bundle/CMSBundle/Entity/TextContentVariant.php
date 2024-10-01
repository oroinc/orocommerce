<?php

namespace Oro\Bundle\CMSBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CMSBundle\Entity\Repository\TextContentVariantRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * Represents Content Variant entity
 */
#[ORM\Entity(repositoryClass: TextContentVariantRepository::class)]
#[ORM\Table(name: 'oro_cms_text_content_variant')]
#[Config]
class TextContentVariant
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ContentBlock::class, inversedBy: 'contentVariants')]
    #[ORM\JoinColumn(name: 'content_block_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?ContentBlock $contentBlock = null;

    /**
     * @var Collection<int, Scope>
     */
    #[ORM\ManyToMany(targetEntity: Scope::class)]
    #[ORM\JoinTable(name: 'oro_cms_txt_cont_variant_scope')]
    #[ORM\JoinColumn(name: 'variant_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'scope_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $scopes = null;

    /**
     * @var string
     */
    #[ORM\Column(type: 'wysiwyg', nullable: true)]
    #[ConfigField(defaultValues: ['attachment' => ['acl_protected' => false]])]
    protected $content;

    /**
     * @var string
     */
    #[ORM\Column(name: 'content_style', type: 'wysiwyg_style', nullable: true)]
    #[ConfigField(defaultValues: ['attachment' => ['acl_protected' => false]])]
    protected $contentStyle;

    /**
     * @var mixed
     */
    #[ORM\Column(name: 'content_properties', type: 'wysiwyg_properties', nullable: true)]
    protected $contentProperties;

    #[ORM\Column(name: 'is_default', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $default = false;

    public function __construct()
    {
        $this->scopes = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ContentBlock
     */
    public function getContentBlock()
    {
        return $this->contentBlock;
    }

    /**
     * @param ContentBlock $contentBlock
     *
     * @return $this
     */
    public function setContentBlock(ContentBlock $contentBlock)
    {
        $this->contentBlock = $contentBlock;

        return $this;
    }

    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * @return $this
     */
    public function resetScopes()
    {
        $this->scopes->clear();

        return $this;
    }

    /**
     * @param Scope $scope
     * @return $this
     */
    public function addScope(Scope $scope)
    {
        if (!$this->scopes->contains($scope)) {
            $this->scopes->add($scope);
        }

        return $this;
    }

    /**
     * @param Scope $scope
     * @return $this
     */
    public function removeScope(Scope $scope)
    {
        if ($this->scopes->contains($scope)) {
            $this->scopes->removeElement($scope);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentStyle()
    {
        return $this->contentStyle;
    }

    /**
     * @param string $contentStyle
     *
     * @return $this
     */
    public function setContentStyle($contentStyle)
    {
        $this->contentStyle = $contentStyle;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getContentProperties()
    {
        return $this->contentProperties;
    }

    /**
     * @param mixed $contentProperties
     *
     * @return $this
     */
    public function setContentProperties($contentProperties)
    {
        $this->contentProperties = $contentProperties;

        return $this;
    }

    /**
     * @param bool $default
     * @return $this
     */
    public function setDefault($default)
    {
        $this->default = (bool)$default;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->default;
    }
}
