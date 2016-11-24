<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentVariantType\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

class ContentVariantStub implements ContentVariantInterface
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var Page
     */
    protected $cmsPage;

    /**
     * @var ArrayCollection|Scope[]
     */
    protected $scopes;

    public function __construct()
    {
        $this->scopes = new ArrayCollection();
    }

    /**
     * @return Collection|Scope[]
     */
    public function getScopes()
    {
        return $this->scopes;
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
     * @param string $type
     * @return ContentVariantStub
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Page
     */
    public function getCmsPage()
    {
        return $this->cmsPage;
    }

    /**
     * @param Page $cmsPage
     * @return ContentVariantStub
     */
    public function setCmsPage($cmsPage)
    {
        $this->cmsPage = $cmsPage;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNode()
    {
        return null;
    }
}
