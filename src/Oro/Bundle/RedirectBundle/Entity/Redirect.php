<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * Stores all redirects for known slugs.
 *
 * @ORM\Table(
 *      name="oro_redirect",
 *      indexes={
 *          @ORM\Index(name="idx_oro_redirect_from_hash", columns={"from_hash"}),
 *          @ORM\Index(name="idx_oro_redirect_redirect_from_prototype", columns={"redirect_from_prototype"}),
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\RedirectBundle\Entity\Repository\RedirectRepository")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-share-sign"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class Redirect
{
    const MOVED_PERMANENTLY = 301;
    const MOVED_TEMPORARY = 302;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="redirect_from_prototype", type="string", length=255, nullable=true)
     */
    protected $fromPrototype;

    /**
     * @var string
     *
     * @ORM\Column(name="redirect_from", type="string", length=1024)
     */
    protected $from;

    /**
     * @var string
     *
     * @ORM\Column(name="from_hash", type="string", length=32)
     */
    protected $fromHash;

    /**
     * @var string
     *
     * @ORM\Column(name="redirect_to_prototype", type="string", length=255, nullable=true)
     */
    protected $toPrototype;

    /**
     * @var string
     *
     * @ORM\Column(name="redirect_to", type="string", length=1024)
     */
    protected $to;

    /**
     * @var integer
     *
     * @ORM\Column(name="redirect_type", type="integer", nullable=false)
     */
    protected $type;

    /**
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\ScopeBundle\Entity\Scope")
     * @ORM\JoinTable(
     *      name="oro_redirect_scope",
     *      joinColumns={
     *          @ORM\JoinColumn(name="redirect_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="scope_id", referencedColumnName="id", onDelete="CASCADE")
     *      }
     * )
     *
     * @var Scope[]|Collection
     */
    protected $scopes;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\RedirectBundle\Entity\Slug", inversedBy="redirects")
     * @ORM\JoinColumn(name="slug_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     *
     * @var Slug
     */
    protected $slug;

    public function __construct()
    {
        $this->scopes = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFromPrototype(): ?string
    {
        return $this->fromPrototype;
    }

    /**
     * @param string $fromPrototype
     * @return $this
     */
    public function setFromPrototype(?string $fromPrototype): Redirect
    {
        $this->fromPrototype = $fromPrototype;

        return $this;
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param string $from
     * @return $this
     */
    public function setFrom($from)
    {
        $this->from = $from;
        $this->fromHash = md5($this->from);

        return $this;
    }

    /**
     * @return string
     */
    public function getToPrototype(): ?string
    {
        return $this->toPrototype;
    }

    /**
     * @param string $toPrototype
     * @return $this
     */
    public function setToPrototype(?string $toPrototype): Redirect
    {
        $this->toPrototype = $toPrototype;

        return $this;
    }

    /**
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param string $to
     * @return $this
     */
    public function setTo($to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

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
     * @param Collection $scopes
     * @return $this
     */
    public function setScopes(Collection $scopes)
    {
        $this->scopes = $scopes;

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
     *
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
     * @return Slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param Slug $slug
     * @return $this
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }
}
