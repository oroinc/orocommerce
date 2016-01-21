<?php

namespace OroB2B\Bundle\AccountBundle\Menu;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\VoidCache;

use Knp\Menu\Factory\ExtensionInterface;
use Knp\Menu\ItemInterface;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

use OroB2B\Bundle\MenuBundle\Menu\BuilderInterface;
use OroB2B\Bundle\AccountBundle\Security\AccountUserProvider;

class AclExtension implements ExtensionInterface
{
    const CACHE_NAMESPACE = 'orob2b_menu_acl';
    const ACL_RESOURCE_ID_KEY = 'aclResourceId';
    const ROUTE_CONTROLLER_KEY = '_controller';
    const CONTROLLER_ACTION_DELIMITER = '::';
    const DEFAULT_ACL_POLICY = true;
    const ACL_POLICY_KEY = 'acl_policy';

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    private $router;

    /**
     * @var AccountUserProvider
     */
    private $accountUserProvider;

    /**
     * @var \Doctrine\Common\Cache\CacheProvider
     */
    private $cache;

    /**
     * @var array
     */
    protected $aclCache = [];

    /**
     * @param RouterInterface $router
     * @param AccountUserProvider $accountUserProvider
     */
    public function __construct(RouterInterface $router, AccountUserProvider $accountUserProvider)
    {
        $this->router = $router;
        $this->accountUserProvider = $accountUserProvider;
        $this->cache = new VoidCache();
    }

    /**
     * Set cache instance
     *
     * @param \Doctrine\Common\Cache\CacheProvider $cache
     */
    public function setCache(CacheProvider $cache)
    {
        $this->cache = $cache;
        $this->cache->setNamespace(self::CACHE_NAMESPACE);
    }

    /**
     * Configures the item with the passed options
     *
     * @param ItemInterface $item
     * @param array $options
     */
    public function buildItem(ItemInterface $item, array $options)
    {
    }

    /**
     * Check Permissions and set options for renderer.
     *
     * @param  array $options
     * @return array
     */
    public function buildOptions(array $options = [])
    {
        return $this->processAcl($options);
    }

    /**
     * Check ACL based on acl_resource_id, route or uri.
     *
     * @param array $options
     *
     * @return array
     */
    protected function processAcl(array $options = [])
    {
        $isAllowed = self::DEFAULT_ACL_POLICY;

        $routeInfo = $this->getRouteInfo($options);
        if ($routeInfo) {
            if (isset($this->aclCache[$routeInfo['key']])) {
                $isAllowed = $this->aclCache[$routeInfo['key']];
            } else {
                $isAllowed = $this->accountUserProvider->isGrantedViewBasic($routeInfo['controller']);
                $this->aclCache[$routeInfo['key']] = $isAllowed;
            }
        }
        $options['extras'][BuilderInterface::IS_ALLOWED_OPTION_KEY] = $isAllowed;

        return $options;
    }

    /**
     * @param  array $options
     * @return array|boolean
     */
    protected function getRouteInfo(array $options = [])
    {
        $key = null;
        $cacheKey = null;
        if (isset($options['uri'])) {
            $cacheKey = $this->getCacheKey($options['uri']);
            if ($this->cache->contains($cacheKey)) {
                $key = $this->cache->fetch($cacheKey);
            } else {
                $key = $this->getRouteInfoByUri($options['uri']);
                $this->cache->save($cacheKey, $key);
            }
        }

        $info = explode(self::CONTROLLER_ACTION_DELIMITER, $key);
        if (count($info) === 2) {
            return [
                'controller' => $info[0],
                'action' => $info[1],
                'key' => $key
            ];
        }

        return false;
    }

    /**
     * Get route info by uri
     *
     * @param  string $uri
     * @return null|string
     */
    protected function getRouteInfoByUri($uri)
    {
        try {
            $routeInfo = $this->router->match($uri);

            return $routeInfo[self::ROUTE_CONTROLLER_KEY];
        } catch (ResourceNotFoundException $e) {
        }

        return null;
    }

    /**
     * Get safe cache key
     *
     * @param  string $value
     * @return string
     */
    protected function getCacheKey($value)
    {
        return md5($value);
    }
}
