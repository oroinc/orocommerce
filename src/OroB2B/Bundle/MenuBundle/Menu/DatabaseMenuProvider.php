<?php

namespace OroB2B\Bundle\MenuBundle\Menu;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;

use Knp\Menu\ItemInterface;
use Knp\Menu\Provider\MenuProviderInterface;

use OroB2B\Bundle\MenuBundle\Entity\Repository\MenuItemRepository;
use OroB2B\Bundle\WebsiteBundle\Locale\LocaleHelper;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class DatabaseMenuProvider implements MenuProviderInterface
{
    const CACHE_NAMESPACE = 'orob2b_menu_instance';
    const LOCALE_OPTION = 'orob2b_website_locale';

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
     * @var LocaleHelper
     */
    protected $localeHelper;

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
     * @param LocaleHelper $localeHelper
     * @param MenuSerializer $serializer
     * @param ManagerRegistry $registry
     */
    public function __construct(
        BuilderInterface $builder,
        LocaleHelper $localeHelper,
        MenuSerializer $serializer,
        ManagerRegistry $registry
    ) {
        $this->builder = $builder;
        $this->localeHelper = $localeHelper;
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
        $locales = $this->localeHelper->getAll();
        foreach ($locales as $locale) {
            $this->buildMenu($alias, [DatabaseMenuProvider::LOCALE_OPTION => $locale]);
        }
    }

    /**
     * @param Locale $locale
     */
    public function rebuildCacheByLocale(Locale $locale)
    {
        if (!$this->cache) {
            return;
        }
        /** @var MenuItemRepository $repo */
        $repo = $this->registry
            ->getManagerForClass($this->entityClass)
            ->getRepository($this->entityClass);

        $menus = $repo->findRoots();
        foreach ($menus as $menu) {
            $alias = $menu->getDefaultTitle()->getString();
            $this->buildMenu($alias, [DatabaseMenuProvider::LOCALE_OPTION => $locale]);
        }
    }

    /**
     * @param $alias
     * @param array $options
     * @return ItemInterface
     */
    protected function buildMenu($alias, array $options = [])
    {
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
     * @return string
     */
    protected function getMenuIdentifier($alias, array $options = [])
    {
        if (array_key_exists(self::LOCALE_OPTION, $options)) {
            $locale = $options[self::LOCALE_OPTION];
        } else {
            $locale = $this->localeHelper->getCurrentLocale();
        }

        return sprintf("%s:%s", $alias, $locale->getCode());
    }
}
