<?php

namespace Oro\Bundle\WebCatalogBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Model\ExtendContentVariant;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="oro_web_catalog_variant")
 * @Config
 */
class ContentVariant extends ExtendContentVariant implements ContentVariantInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="system_page_route", type="string", length=255, nullable=true)
     */
    protected $systemPageRoute;

    /**
     * @var ContentNode
     *
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\WebCatalogBundle\Entity\ContentNode",
     *     inversedBy="contentVariants"
     * )
     * @ORM\JoinColumn(name="node_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $node;

    /**
     * @var Collection|Scope[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\ScopeBundle\Entity\Scope"
     * )
     * @ORM\JoinTable(name="oro_web_catalog_variant_scope",
     *      joinColumns={
     *          @ORM\JoinColumn(name="variant_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="scope_id", referencedColumnName="id", onDelete="CASCADE")
     *      }
     * )
     */
    protected $scopes;

    public function __construct()
    {
        parent::__construct();

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
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getSystemPageRoute()
    {
        return $this->systemPageRoute;
    }

    /**
     * @param string $systemPageRoute
     *
     * @return $this
     */
    public function setSystemPageRoute($systemPageRoute)
    {
        $this->systemPageRoute = $systemPageRoute;

        return $this;
    }

    /**
     * @return ContentNode
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @param ContentNode $node
     *
     * @return $this
     */
    public function setNode(ContentNode $node)
    {
        $this->node = $node;

        return $this;
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
}
