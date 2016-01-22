<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit\Menu;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\MenuBundle\Menu\BuilderInterface;
use OroB2B\Bundle\MenuBundle\Menu\DatabaseMenuProvider;
use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\MenuBundle\Menu\MenuSerializer;
use OroB2B\Bundle\WebsiteBundle\Locale\LocaleHelper;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class DatabaseMenuProviderTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'TestBundle:MenuItem';

    /**
     * @var DatabaseMenuProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|BuilderInterface
     */
    protected $builder;

    /**
     * @var Locale
     */
    protected $currentLocale;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LocaleHelper
     */
    protected $localeHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|MenuSerializer
     */
    protected $serializer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CacheProvider
     */
    protected $cache;

    public function setUp()
    {
        $this->builder = $this->getMock('OroB2B\Bundle\MenuBundle\Menu\BuilderInterface');

        $this->currentLocale = new Locale();
        $this->currentLocale->setCode('en');
        $this->localeHelper = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Locale\LocaleHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeHelper->expects($this->any())
            ->method('getCurrentLocale')
            ->willReturn($this->currentLocale);

        $this->serializer = $this->getMockBuilder('OroB2B\Bundle\MenuBundle\Menu\MenuSerializer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new DatabaseMenuProvider(
            $this->builder,
            $this->localeHelper,
            $this->serializer,
            $this->registry
        );

        $this->provider->setEntityClass(self::ENTITY_CLASS);

        $this->cache = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->disableOriginalConstructor()
            ->setMethods(['fetch', 'save', 'contains', 'delete'])
            ->getMockForAbstractClass();
    }

    public function testGet()
    {
        $this->provider->setCache($this->cache);

        $alias = 'test_menu';
        $options = ['extras' => [MenuItem::LOCALE_OPTION => $this->currentLocale]];
        $menu = $this->getMock('Knp\Menu\ItemInterface');
        $serializedMenu = ['menuItem1.1', 'menuItem2.1'];

        $this->builder->expects($this->once())
            ->method('build')
            ->with($alias, $options)
            ->willReturn($menu);
        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($menu)
            ->willReturn($serializedMenu);
        $this->cache->expects($this->once())
            ->method('save')
            ->with('test_menu:en', $serializedMenu);
        $actual = $this->provider->get($alias, $options);

        $this->assertEquals($menu, $actual);
    }

    public function testGetCached()
    {
        $this->provider->setCache($this->cache);
        $alias = 'test_menu';
        $options = [];
        $serializedMenu = ['menuItem1.1', 'menuItem2.1'];
        $menu = $this->getMock('Knp\Menu\ItemInterface');

        $this->cache->expects($this->once())
            ->method('contains')
            ->with('test_menu:en')
            ->willReturn(true);
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('test_menu:en')
            ->willReturn($serializedMenu);
        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with($serializedMenu)
            ->willReturn($menu);

        $actual = $this->provider->get($alias, $options);

        $this->assertEquals($menu, $actual);
    }

    /**
     * @dataProvider  testHasWithCacheDataProvider
     *
     * @param string $alias
     * @param array $options
     * @param string $menuIdentifier
     * @param bool $expected
     */
    public function testHasWithCache($alias, array $options, $menuIdentifier, $expected)
    {
        $this->provider->setCache($this->cache);

        $this->cache->expects($this->once())
            ->method('contains')
            ->with($menuIdentifier)
            ->willReturn($expected);

        $actual = $this->provider->has($alias, $options);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function testHasWithCacheDataProvider()
    {
        return [
            [
                'alias' => 'test_menu',
                'options' => [],
                'menuIdentifier' => 'test_menu:en',
                'expected' => true
            ],
            [
                'alias' => 'test_menu2',
                'options' => ['extras' => [MenuItem::LOCALE_OPTION => (new Locale)->setCode('kz')]],
                'menuIdentifier' => 'test_menu2:kz',
                'expected' => false
            ]
        ];
    }

    /**
     * @dataProvider hasWithoutCacheDataProvider
     * @param string $alias
     * @param bool $expected
     */
    public function testHasWithoutCache($alias, $expected)
    {
        $this->builder->expects($this->once())
            ->method('isSupported')
            ->with($alias)
            ->willReturn($expected);

        $actual = $this->provider->has($alias);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function hasWithoutCacheDataProvider()
    {
        return [
            [
                'alias' => 'test_menu',
                'expected' => true
            ],
            [
                'alias' => 'test_menu2',
                'expected' => false
            ]
        ];
    }

    public function testRebuildCacheByAliasWithoutCache()
    {
        $this->localeHelper->expects($this->never())
            ->method('getAll');
        $this->provider->rebuildCacheByAlias('test_menu');
    }

    public function testRebuildCacheByAlias()
    {
        $this->provider->setCache($this->cache);

        $alias = 'test_menu';
        $menuEn = $this->getMock('Knp\Menu\ItemInterface');
        $menuKz = $this->getMock('Knp\Menu\ItemInterface');
        $serializedMenuEn = ['menuItem1en', 'menuItem2kz'];
        $serializedMenuKz = ['menuItem1kz', 'menuItem2kz'];
        $enLocale = (new Locale())->setCode('en');
        $kzLocale = (new Locale())->setCode('kz');
        $this->localeHelper->expects($this->once())
            ->method('getAll')
            ->willReturn(
                [
                    $enLocale,
                    $kzLocale,
                ]
            );
        $this->builder->expects($this->exactly(2))
            ->method('build')
            ->willReturnMap(
                [
                    [$alias, ['extras' => [MenuItem::LOCALE_OPTION => $enLocale]], $menuEn],
                    [$alias, ['extras' => [MenuItem::LOCALE_OPTION => $kzLocale]], $menuKz]
                ]
            );
        $this->serializer->expects($this->exactly(2))
            ->method('serialize')
            ->willReturnMap(
                [
                    [$menuEn, $serializedMenuEn],
                    [$menuKz, $serializedMenuKz],
                ]
            );
        $this->cache->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive(
                ['test_menu:en', $serializedMenuEn],
                ['test_menu:kz', $serializedMenuKz]
            );
        $this->provider->rebuildCacheByAlias($alias);
    }

    public function testClearCacheByAliasWithoutCache()
    {
        $this->localeHelper->expects($this->never())
            ->method('getAll');
        $this->provider->clearCacheByAlias('test_menu');
    }

    public function testClearCacheByAlias()
    {
        $this->provider->setCache($this->cache);

        $alias = 'test_menu';
        $enLocale = (new Locale())->setCode('en');
        $kzLocale = (new Locale())->setCode('kz');
        $this->localeHelper->expects($this->once())
            ->method('getAll')
            ->willReturn(
                [
                    $enLocale,
                    $kzLocale,
                ]
            );
        $this->cache->expects($this->exactly(2))
            ->method('delete')
            ->withConsecutive(
                ['test_menu:en'],
                ['test_menu:kz']
            );
        $this->provider->clearCacheByAlias($alias);
    }

    public function testRebuildCacheByLocaleWithoutCache()
    {
        $this->registry->expects($this->never())
            ->method('getManagerForClass');
        $this->provider->rebuildCacheByLocale($this->currentLocale);
    }

    public function testRebuildCacheByLocale()
    {
        $this->provider->setCache($this->cache);
        $locale = new Locale();
        $locale->setCode('kz');
        $menu1root = $this->createRootMenuItem('menu1');
        $menu2root = $this->createRootMenuItem('menu2');
        $menu1 = $this->getMock('Knp\Menu\ItemInterface');
        $menu2 = $this->getMock('Knp\Menu\ItemInterface');
        $serializedMenu1 = ['menuItem1.1', 'menuItem2.1'];
        $serializedMenu2 = ['menuItem1.2', 'menuItem2.2', 'meuItem2.3'];

        $repo = $this->getMockBuilder('OroB2B\Bundle\MenuBundle\Entity\Repository\MenuItemRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('findRoots')
            ->willReturn(
                [
                    $menu1root,
                    $menu2root
                ]
            );
        $om = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $om->expects($this->once())
            ->method('getRepository')
            ->with(self::ENTITY_CLASS)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($om);

        $this->builder->expects($this->exactly(2))
            ->method('build')
            ->willReturnMap(
                [
                    ['menu1', ['extras'=> [MenuItem::LOCALE_OPTION => $locale]], $menu1],
                    ['menu2', ['extras'=> [MenuItem::LOCALE_OPTION => $locale]], $menu2]
                ]
            );
        $this->serializer->expects($this->exactly(2))
            ->method('serialize')
            ->willReturnMap(
                [
                    [$menu1, $serializedMenu1],
                    [$menu2, $serializedMenu2],
                ]
            );
        $this->cache->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive(
                ['menu1:kz', $serializedMenu1],
                ['menu2:kz', $serializedMenu2]
            );

        $this->provider->rebuildCacheByLocale($locale);
    }

    public function testClearCacheByLocaleWithoutCache()
    {
        $this->registry->expects($this->never())
            ->method('getManagerForClass');
        $this->provider->clearCacheByLocale($this->currentLocale);
    }

    public function testClearCacheByLocale()
    {
        $this->provider->setCache($this->cache);
        $locale = new Locale();
        $locale->setCode('kz');
        $menu1root = $this->createRootMenuItem('menu1');
        $menu2root = $this->createRootMenuItem('menu2');

        $repo = $this->getMockBuilder('OroB2B\Bundle\MenuBundle\Entity\Repository\MenuItemRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('findRoots')
            ->willReturn(
                [
                    $menu1root,
                    $menu2root
                ]
            );
        $om = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $om->expects($this->once())
            ->method('getRepository')
            ->with(self::ENTITY_CLASS)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($om);

        $this->cache->expects($this->exactly(2))
            ->method('delete')
            ->withConsecutive(
                ['menu1:kz'],
                ['menu2:kz']
            );

        $this->provider->clearCacheByLocale($locale);
    }

    /**
     * @param string $title
     * @return MenuItem
     */
    protected function createRootMenuItem($title)
    {
        $menu = new MenuItem();
        $fallbackValue = new LocalizedFallbackValue();
        $fallbackValue->setString($title);
        $menu->addTitle($fallbackValue);

        return $menu;
    }
}
