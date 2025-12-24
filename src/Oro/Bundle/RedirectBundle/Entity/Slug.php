<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Helper\SlugScopeHelper;
use Oro\Bundle\RedirectBundle\Helper\UrlParameterHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * Slug entity class.
 */
#[ORM\Entity(repositoryClass: SlugRepository::class)]
#[ORM\Table(name: 'oro_redirect_slug')]
#[ORM\Index(columns: ['url_hash'], name: 'oro_redirect_slug_url_hash')]
#[ORM\Index(columns: ['slug_prototype'], name: 'oro_redirect_slug_slug')]
#[ORM\Index(columns: ['route_name'], name: 'oro_redirect_slug_route')]
#[ORM\Index(columns: ['parameters_hash'], name: 'oro_redirect_slug_parameters_hash_idx')]
#[ORM\UniqueConstraint(
    name: 'oro_redirect_slug_deferrable_uidx',
    columns: ['organization_id', 'url_hash', 'scopes_hash']
)]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-share-square'],
        'security' => ['type' => 'ACL', 'group_name' => ''],
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'organization',
            'owner_column_name' => 'organization_id'
        ]
    ]
)]
class Slug implements OrganizationAwareInterface
{
    use OrganizationAwareTrait;

    public const DELIMITER = '/';

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'url', type: Types::STRING, length: 1024)]
    protected ?string $url = null;

    #[ORM\Column(name: 'slug_prototype', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $slugPrototype = null;

    #[ORM\Column(name: 'url_hash', type: Types::STRING, length: 32)]
    protected ?string $urlHash = null;

    #[ORM\Column(name: 'route_name', type: Types::STRING, length: 255)]
    protected ?string $routeName = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'route_parameters', type: Types::ARRAY)]
    protected $routeParameters = [];

    /**
     *
     * @var Collection<int, Scope>
     */
    #[ORM\ManyToMany(targetEntity: Scope::class)]
    #[ORM\JoinTable(name: 'oro_slug_scope')]
    #[ORM\JoinColumn(name: 'slug_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'scope_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $scopes = null;

    #[ORM\Column(name: 'scopes_hash', type: Types::STRING, length: 32)]
    protected ?string $scopesHash = null;

    /**
     * @var Collection<int, Redirect>
     */
    #[ORM\OneToMany(mappedBy: 'slug', targetEntity: Redirect::class)]
    protected ?Collection $redirects = null;

    #[ORM\ManyToOne(targetEntity: Localization::class)]
    #[ORM\JoinColumn(name: 'localization_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?Localization $localization = null;

    #[ORM\Column(name: 'parameters_hash', type: Types::STRING, length: 32)]
    protected ?string $parametersHash = null;

    public function __construct()
    {
        $this->redirects = new ArrayCollection();
        $this->scopes = new ArrayCollection();
        $this->fillScopesHash();
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
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;
        $this->urlHash = md5($this->url);

        return $this;
    }

    /**
     * @return string
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * @param string $routeName
     * @return $this
     */
    public function setRouteName($routeName)
    {
        $this->routeName = $routeName;

        return $this;
    }

    /**
     * @return array
     */
    public function getRouteParameters()
    {
        return $this->routeParameters;
    }

    /**
     * @param array $routeParameters
     * @return $this
     */
    public function setRouteParameters(array $routeParameters)
    {
        UrlParameterHelper::normalizeNumericTypes($routeParameters);
        $this->routeParameters = $routeParameters;
        $this->parametersHash = UrlParameterHelper::hashParams($routeParameters);

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
            $this->fillScopesHash();
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
            $this->fillScopesHash();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function resetScopes()
    {
        $this->scopes->clear();
        $this->fillScopesHash();

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getUrl();
    }

    /**
     * @return Redirect[]|Collection
     */
    public function getRedirects()
    {
        return $this->redirects;
    }

    /**
     * @param Redirect $redirect
     *
     * @return $this
     */
    public function addRedirect(Redirect $redirect)
    {
        if (!$this->redirects->contains($redirect)) {
            $this->redirects->add($redirect);
            $redirect->setSlug($this);
        }

        return $this;
    }

    /**
     * @param Redirect $redirect
     *
     * @return $this
     */
    public function removeRedirect(Redirect $redirect)
    {
        if ($this->redirects->contains($redirect)) {
            $this->redirects->removeElement($redirect);
        }

        return $this;
    }

    /**
     * @return Localization|null
     */
    public function getLocalization()
    {
        return $this->localization;
    }

    /**
     * @param Localization|null $localization
     * @return $this
     */
    public function setLocalization(Localization $localization = null)
    {
        $this->localization = $localization;
        $this->fillScopesHash();

        return $this;
    }

    /**
     * @return string
     */
    public function getSlugPrototype()
    {
        return $this->slugPrototype;
    }

    /**
     * @param string $slugPrototype
     * @return Slug
     */
    public function setSlugPrototype($slugPrototype)
    {
        $this->slugPrototype = $slugPrototype;

        return $this;
    }

    /**
     * @return string
     */
    public function getParametersHash()
    {
        return $this->parametersHash;
    }

    /**
     * @return string
     */
    public function getScopesHash()
    {
        return $this->scopesHash;
    }

    public function fillScopesHash(): void
    {
        $this->scopesHash = SlugScopeHelper::getScopesHash($this->scopes, $this->localization);
    }
}
