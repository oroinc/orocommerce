<?php

namespace Oro\Component\WebCatalog\Test\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareTrait;
use Oro\Component\WebCatalog\Entity\ContentNodeAwareInterface;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

/**
 * Stub content variant that can be used for unit testing.
 */
abstract class AbstractContentVariantStub implements
    ContentVariantInterface,
    ContentNodeAwareInterface,
    SlugAwareInterface
{
    use SlugAwareTrait;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var ArrayCollection
     */
    protected $scopes;

    /**
     * @var ContentNodeInterface
     */
    protected $node;

    /**
     * @var bool
     */
    protected $default;

    /**
     * @var bool
     */
    protected $overrideVariantConfiguration;

    protected int $id = 1;

    public function __construct()
    {
        $this->scopes = new ArrayCollection();
    }

    /**
     * @return ArrayCollection
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * @param object $scope
     * @return $this
     */
    public function addScope($scope)
    {
        if (!$this->scopes->contains($scope)) {
            $this->scopes->add($scope);
        }

        return $this;
    }

    /**
     * @param object $scope
     * @return $this
     */
    public function removeScope($scope)
    {
        if ($this->scopes->contains($scope)) {
            $this->scopes->removeElement($scope);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNode()
    {
        return $this->node;
    }

    public function setNode(ContentNodeInterface $node)
    {
        $this->node = $node;
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

    public function isOverrideVariantConfiguration(): bool
    {
        return $this->overrideVariantConfiguration;
    }

    public function setOverrideVariantConfiguration(bool $overrideVariantConfiguration): self
    {
        $this->overrideVariantConfiguration = $overrideVariantConfiguration;

        return $this;
    }
}
