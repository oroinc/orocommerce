<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;
use Oro\Bundle\RedirectBundle\Helper\UrlParameterHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * Slug entity class.
 *
 * @ORM\Table(
 *     name="oro_redirect_slug",
 *     indexes={
 *         @ORM\Index(name="oro_redirect_slug_url_hash", columns={"url_hash"}),
 *         @ORM\Index(name="oro_redirect_slug_slug", columns={"slug_prototype"}),
 *         @ORM\Index(name="oro_redirect_slug_route", columns={"route_name"}),
 *         @ORM\Index(name="oro_redirect_slug_parameters_hash_idx", columns={"parameters_hash"})
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *             name="oro_redirect_slug_uidx",
 *             columns={"organization_id","url_hash","scopes_hash"}
 *         )
 *     }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-share-square"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class Slug implements OrganizationAwareInterface
{
    use OrganizationAwareTrait;

    public const DELIMITER = '/';

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
     * @ORM\Column(name="url", type="string", length=1024)
     */
    protected $url;

    /**
     * @var string
     *
     * @ORM\Column(name="slug_prototype", type="string", length=255, nullable=true)
     */
    protected $slugPrototype;

    /**
     * @var string
     *
     * @ORM\Column(name="url_hash", type="string", length=32)
     */
    protected $urlHash;

    /**
     * @var string
     *
     * @ORM\Column(name="route_name", type="string", length=255)
     */
    protected $routeName;

    /**
     * @var array
     *
     * @ORM\Column(name="route_parameters", type="array")
     */
    protected $routeParameters = [];

    /**
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\ScopeBundle\Entity\Scope")
     * @ORM\JoinTable(
     *      name="oro_slug_scope",
     *      joinColumns={
     *          @ORM\JoinColumn(name="slug_id", referencedColumnName="id", onDelete="CASCADE")
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
     * @var string
     *
     * @ORM\Column(name="scopes_hash", type="string", length=32)
     */
    protected $scopesHash;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\RedirectBundle\Entity\Redirect",
     *     mappedBy="slug"
     * )
     *
     * @var Redirect[]|Collection
     */
    protected $redirects;

    /**
     * @var Localization|null
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\LocaleBundle\Entity\Localization")
     * @ORM\JoinColumn(name="localization_id", referencedColumnName="id", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $localization;

    /**
     * @var string
     *
     * @ORM\Column(name="parameters_hash", type="string", length=32)
     */
    protected $parametersHash;

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
        $scopeIds = [];
        foreach ($this->scopes as $scope) {
            $scopeIds[] = $scope->getId();
        }

        sort($scopeIds);
        $this->scopesHash = md5(sprintf(
            '%s:%s',
            implode(',', $scopeIds),
            $this->localization ? $this->localization->getId() : ''
        ));
    }
}
