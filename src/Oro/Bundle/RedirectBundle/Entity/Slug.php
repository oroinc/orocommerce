<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * @ORM\Table(name="oro_redirect_slug", indexes={@ORM\Index(name="oro_redirect_slug_url_hash", columns={"url_hash"})})
 * @ORM\Entity(repositoryClass="Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository")
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
class Slug
{
    const DELIMITER = '/';

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
     * @ORM\Column(type="string", length=1024)
     */
    protected $url;

    /**
     * @var string
     *
     * @ORM\Column(name="url_hash", type="string", length=32)
     */
    protected $urlHash;

    /**
     * @var string
     *
     * @ORM\Column(name="route_name", type="string", length=255, nullable=true)
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

    public function __construct()
    {
        $this->redirects = new ArrayCollection();
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
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getSlugUrl()
    {
        $latestSlash = strrpos($this->url, self::DELIMITER);

        if ($latestSlash !== false) {
            return substr($this->url, $latestSlash + 1);
        } else {
            return $this->url;
        }
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
    public function setRouteParameters($routeParameters)
    {
        $this->routeParameters = $routeParameters;

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
     *
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
     * @return $this
     */
    public function resetScopes()
    {
        $this->scopes->clear();
        
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

        return $this;
    }
}
