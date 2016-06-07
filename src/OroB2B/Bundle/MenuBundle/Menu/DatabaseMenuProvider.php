<?php

namespace OroB2B\Bundle\MenuBundle\Menu;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;

use Knp\Menu\ItemInterface;
use Knp\Menu\Provider\MenuProviderInterface;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\MenuBundle\Entity\Repository\MenuItemRepository;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DatabaseMenuProvider implements MenuProviderInterface
{
    const CACHE_NAMESPACE = 'orob2b_menu_instance';

    /**
     * @var array
     */
    protected $menus = [];

    /**
     * @var BuilderInterface
     */
    protected $builder;

    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @var MenuSerializer
     */
    protected $serializer;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @param BuilderInterface $builder
     * @param LocalizationHelper $localizationHelper
     * @param MenuSerializer $serializer
     * @param ManagerRegistry $registry
     */
    public function __construct(
        BuilderInterface $builder,
        LocalizationHelper $localizationHelper,
        MenuSerializer $serializer,
        ManagerRegistry $registry
    ) {
        $this->builder = $builder;
        $this->localizationHelper = $localizationHelper;
        $this->serializer = $serializer;
        $this->registry = $registry;
    }

    /**
     * Set cache instance
     *
     * @param CacheProvider $cache
     */
    public function setCache(CacheProvider $cache)
    {
        $this->cache = $cache;
        $this->cache->setNamespace(self::CACHE_NAMESPACE);
    }

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function get($alias, array $options = [])
    {
        $menuIdentifier = $this->getMenuIdentifier($alias, $options);

        if (!array_key_exists($menuIdentifier, $this->menus)) {
            if ($this->cache && $this->cache->contains($menuIdentifier)) {
                $menuData = $this->cache->fetch($menuIdentifier);
                $menu = $this->serializer->deserialize($menuData);
            } else {
                $menu = $this->buildMenu($alias, $options);
            }
            $this->menus[$menuIdentifier] = $menu;
        }

        return $this->menus[$menuIdentifier];
    }

    /**
     * {@inheritdoc}
     */
    public function has($alias, array $options = [])
    {
        $menuIdentifier = $this->getMenuIdentifier($alias, $options);

        if ($this->cache && $this->cache->contains($menuIdentifier)) {
            return true;
        }

        return $this->builder->isSupported($alias);
    }

    /**
     * @param string $alias
     */
    public function rebuildCacheByAlias($alias)
    {
        if (!$this->cache) {
            return;
        }
        $localizations = $this->localizationHelper->getAll();
        foreach ($localizations as $localization) {
            $this->buildMenu($alias, ['extras' => [MenuItem::LOCALE_OPTION => $localization]]);
        }
    }

    /**
     * @param MenuItem $menuItem
     */
    public function rebuildCacheByMenuItem(MenuItem $menuItem)
    {
        if (!$this->cache) {
            return;
        }

        // todo: check do we need always get root from parent, because it doesn't exist in the entity on persist
        $rootId = $menuItem->getRoot();
        if (!$rootId) {
            return;
        }
        /** @var MenuItem $root */
        $root = $this->getRepository()->find($rootId);
        if (!$root) {
            return;
        }
        $alias = $root->getDefaultTitle()->getString();
        $this->rebuildCacheByAlias($alias);
    }

    /**
     * @param string $alias
     */
    public function clearCacheByAlias($alias)
    {
        if (!$this->cache) {
            return;
        }
        $localizations = $this->localizationHelper->getAll();
        foreach ($localizations as $localization) {
            $this->clearMenuCache($alias, ['extras' => [MenuItem::LOCALE_OPTION => $localization]]);
        }
    }

    /**
     * @param Localization $localization
     */
    public function rebuildCacheByLocalization(Localization $localization)
    {
        if (!$this->cache) {
            return;
        }
        $menus = $this->getRoots();
        foreach ($menus as $menu) {
            $alias = $menu->getDefaultTitle()->getString();
            $this->buildMenu($alias, ['extras' => [MenuItem::LOCALE_OPTION => $localization]]);
        }
    }

    /**
     * @param Localization $localization
     */
    public function clearCacheByLocalization(Localization $localization)
    {
        if (!$this->cache) {
            return;
        }
        $menus = $this->getRoots();
        foreach ($menus as $menu) {
            $alias = $menu->getDefaultTitle()->getString();
            $this->clearMenuCache($alias, ['extras' => [MenuItem::LOCALE_OPTION => $localization]]);
        }
    }

    /**
     * @param $alias
     * @param array $options
     * @return ItemInterface
     */
    protected function buildMenu($alias, array $options = [])
    {
        $this->setDefaultLocalizationIfNotExists($options);
        $menu = $this->builder->build($alias, $options);
        if ($this->cache) {
            $menuIdentifier = $this->getMenuIdentifier($alias, $options);
            $this->cache->save($menuIdentifier, $this->serializer->serialize($menu));
        }

        return $menu;
    }

    /**
     * @param string $alias
     * @param array $options
     */
    protected function clearMenuCache($alias, array $options = [])
    {
        $menuIdentifier = $this->getMenuIdentifier($alias, $options);
        $this->cache->delete($menuIdentifier);
    }

    /**
     * @param string $alias
     * @param array $options
     * @return string
     */
    protected function getMenuIdentifier($alias, array $options = [])
    {
        $this->setDefaultLocalizationIfNotExists($options);
        /* @var $localization Localization */
        $localization = $options['extras'][MenuItem::LOCALE_OPTION];

        return sprintf("%s:%s", $alias, $localization->getId());
    }

    /**
     * @param $options
     */
    protected function setDefaultLocalizationIfNotExists(&$options)
    {
        if (!array_key_exists('extras', $options) || !array_key_exists(MenuItem::LOCALE_OPTION, $options['extras'])) {
            $options['extras'][MenuItem::LOCALE_OPTION] = $this->localizationHelper->getCurrentLocalization();
        }
    }

    /**
     * @return MenuItemRepository
     */
    protected function getRepository()
    {
        return $this->registry
            ->getManagerForClass($this->entityClass)
            ->getRepository($this->entityClass);
    }

    /**
     * @return MenuItem[]
     */
    protected function getRoots()
    {
        return $this->getRepository()
            ->findRoots();
    }
}
