<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\BusinessUnitAwareTrait;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Entity\ScopeCollectionAwareInterface;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\Repository\SearchTermRepository;

/**
 * Represents Search Term
 */
#[ORM\Entity(repositoryClass: SearchTermRepository::class)]
#[ORM\Table(name: 'oro_website_search_search_term')]
#[Config(
    routeName: 'oro_website_search_term_index',
    routeView: 'oro_website_search_term_view',
    routeUpdate: 'oro_website_search_term_update',
    defaultValues: [
        'ownership' => [
            'owner_type' => 'BUSINESS_UNIT',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'business_unit_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id',
        ],
        'security' => [
            'type' => 'ACL',
            'group_name' => '',
        ],
    ]
)]
class SearchTerm implements
    ScopeCollectionAwareInterface,
    DatesAwareInterface,
    ExtendEntityInterface,
    OrganizationAwareInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;
    use BusinessUnitAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'phrases', type: Types::TEXT, nullable: false)]
    protected ?string $phrases = '';

    #[ORM\Column(name: 'action_type', type: Types::STRING, length: 128, nullable: false)]
    protected ?string $actionType = '';

    #[ORM\Column(name: 'modify_action_type', type: Types::STRING, length: 128, nullable: true)]
    protected ?string $modifyActionType = '';

    #[ORM\Column(name: 'redirect_action_type', type: Types::STRING, length: 128, nullable: true)]
    protected ?string $redirectActionType = '';

    #[ORM\Column(name: 'redirect_uri', type: Types::TEXT, nullable: true)]
    protected ?string $redirectUri = null;

    #[ORM\Column(name: 'redirect_system_page', type: Types::TEXT, nullable: true)]
    protected ?string $redirectSystemPage = null;

    #[ORM\Column(name: 'redirect_301', type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    protected bool $redirect301 = false;

    /**
     * @var Collection<Scope>
     */
    #[ORM\ManyToMany(
        targetEntity: Scope::class,
    )]
    #[ORM\JoinTable(name: 'oro_website_search_search_term_scopes')]
    #[ORM\JoinColumn(name: 'search_term_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'scope_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Collection $scopes;

    #[ORM\Column(name: 'partial_match', type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    protected bool $partialMatch = false;

    public function __construct()
    {
        $this->scopes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhrases(): string
    {
        return $this->phrases;
    }

    public function setPhrases(?string $phrases): self
    {
        $this->phrases = $phrases;

        return $this;
    }

    /**
     * @return Collection<Scope>
     */
    public function getScopes(): Collection
    {
        return $this->scopes;
    }

    /**
     * @param Collection<Scope> $scopes
     */
    public function setScopes(Collection $scopes): self
    {
        $this->scopes = $scopes;

        return $this;
    }

    public function addScope(Scope $scope): self
    {
        if (!$this->scopes->contains($scope)) {
            $this->scopes->add($scope);
        }

        return $this;
    }

    public function removeScope(Scope $scope): self
    {
        if ($this->scopes->contains($scope)) {
            $this->scopes->removeElement($scope);
        }

        return $this;
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function setActionType(?string $actionType): self
    {
        $this->actionType = $actionType;

        return $this;
    }

    public function getModifyActionType(): ?string
    {
        return $this->modifyActionType;
    }

    public function setModifyActionType(?string $modifyActionType): self
    {
        $this->modifyActionType = $modifyActionType;

        return $this;
    }

    public function getRedirectActionType(): ?string
    {
        return $this->redirectActionType;
    }

    public function setRedirectActionType(?string $redirectActionType): self
    {
        $this->redirectActionType = $redirectActionType;

        return $this;
    }

    public function getRedirectUri(): ?string
    {
        return $this->redirectUri;
    }

    public function setRedirectUri(?string $redirectUri): self
    {
        $this->redirectUri = $redirectUri;

        return $this;
    }

    public function getRedirectSystemPage(): ?string
    {
        return $this->redirectSystemPage;
    }

    public function setRedirectSystemPage(?string $redirectSystemPage): self
    {
        $this->redirectSystemPage = $redirectSystemPage;

        return $this;
    }

    public function isRedirect301(): bool
    {
        return $this->redirect301;
    }

    public function setRedirect301(?bool $redirect301): self
    {
        $this->redirect301 = (bool)$redirect301;

        return $this;
    }

    public function isPartialMatch(): bool
    {
        return $this->partialMatch;
    }

    public function setPartialMatch(?bool $partialMatch): self
    {
        $this->partialMatch = (bool)$partialMatch;

        return $this;
    }
}
