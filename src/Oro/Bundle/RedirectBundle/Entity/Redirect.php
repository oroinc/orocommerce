<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\RedirectBundle\Entity\Repository\RedirectRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * Stores all redirects for known slugs.
 */
#[ORM\Entity(repositoryClass: RedirectRepository::class)]
#[ORM\Table(name: 'oro_redirect')]
#[ORM\Index(columns: ['from_hash'], name: 'idx_oro_redirect_from_hash')]
#[ORM\Index(columns: ['redirect_from_prototype'], name: 'idx_oro_redirect_redirect_from_prototype')]
#[Config(
    defaultValues: ['entity' => ['icon' => 'icon-share-sign'], 'security' => ['type' => 'ACL', 'group_name' => '']]
)]
class Redirect
{
    const MOVED_PERMANENTLY = 301;
    const MOVED_TEMPORARY = 302;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'redirect_from_prototype', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $fromPrototype = null;

    #[ORM\Column(name: 'redirect_from', type: Types::STRING, length: 1024)]
    protected ?string $from = null;

    #[ORM\Column(name: 'from_hash', type: Types::STRING, length: 32)]
    protected ?string $fromHash = null;

    #[ORM\Column(name: 'redirect_to_prototype', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $toPrototype = null;

    #[ORM\Column(name: 'redirect_to', type: Types::STRING, length: 1024)]
    protected ?string $to = null;

    #[ORM\Column(name: 'redirect_type', type: Types::INTEGER, nullable: false)]
    protected ?int $type = null;

    /**
     *
     * @var Collection<int, Scope>
     */
    #[ORM\ManyToMany(targetEntity: Scope::class)]
    #[ORM\JoinTable(name: 'oro_redirect_scope')]
    #[ORM\JoinColumn(name: 'redirect_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'scope_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $scopes = null;

    #[ORM\ManyToOne(targetEntity: Slug::class, inversedBy: 'redirects')]
    #[ORM\JoinColumn(name: 'slug_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?Slug $slug = null;

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

    public function getFromPrototype(): ?string
    {
        return $this->fromPrototype;
    }

    /**
     * @param string|null $fromPrototype
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

    public function getToPrototype(): ?string
    {
        return $this->toPrototype;
    }

    /**
     * @param string|null $toPrototype
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
