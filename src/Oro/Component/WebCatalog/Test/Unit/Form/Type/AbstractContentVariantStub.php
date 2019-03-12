<?php

namespace Oro\Component\WebCatalog\Test\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\WebCatalog\Entity\ContentNodeAwareInterface;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

/**
 * Stub content variant that can be used for unit testing.
 */
abstract class AbstractContentVariantStub implements ContentVariantInterface, ContentNodeAwareInterface
{
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
        return 1;
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

    /**
     * @param ContentNodeInterface $node
     */
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

    /**
     * @return bool
     */
    public function isOverrideVariantConfiguration(): bool
    {
        return $this->overrideVariantConfiguration;
    }

    /**
     * @param bool $overrideVariantConfiguration
     * @return self
     */
    public function setOverrideVariantConfiguration(bool $overrideVariantConfiguration): self
    {
        $this->overrideVariantConfiguration = $overrideVariantConfiguration;

        return $this;
    }
}
