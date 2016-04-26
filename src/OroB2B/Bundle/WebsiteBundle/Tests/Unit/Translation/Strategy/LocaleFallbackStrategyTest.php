<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Translation\Strategy;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\WebsiteBundle\Entity\Repository\LocaleRepository;
use OroB2B\Bundle\WebsiteBundle\Translation\Strategy\LocaleFallbackStrategy;

class LocaleFallbackStrategyTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    /**
     * @var LocaleFallbackStrategy
     */
    protected $strategy;

    protected function setUp()
    {
        $this->doctrine = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->cache = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(['fetch', 'contains', 'save', 'delete'])->getMockForAbstractClass();
        $this->strategy = new LocaleFallbackStrategy($this->doctrine, $this->cache);
    }

    /**
     * @dataProvider getLocaleFallbacksDataProvider
     *
     * @param array|null $entities
     * @param array $locales
     */
    public function testGetLocaleFallbacks($entities, array $locales)
    {
        $this->cache->expects($this->once())
            ->method('contains')
            ->with(LocaleFallbackStrategy::CACHE_KEY)
            ->willReturn(false);
        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroB2BWebsiteBundle:Locale')
            ->willReturn($em);
        /** @var LocaleRepository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Entity\Repository\LocaleRepository')
            ->disableOriginalConstructor()->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BWebsiteBundle:Locale')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('findRootsWithChildren')
            ->willReturn($entities);
        $repository->expects($this->once())
            ->method('findRootsWithChildren')
            ->willReturn($entities);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(LocaleFallbackStrategy::CACHE_KEY, $locales)
            ->willReturn((bool)$entities);
        $this->cache->expects($this->never())
            ->method('fetch');
        $this->assertEquals($locales, $this->strategy->getLocaleFallbacks());
    }

    /**
     * @return array
     */
    public function getLocaleFallbacksDataProvider()
    {
        $secondLevelLevelEn = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Locale', ['code' => 'en_FR']);
        $firstLevelEn = $this->getEntity(
            'OroB2B\Bundle\WebsiteBundle\Entity\Locale',
            ['code' => 'en_EN', 'childLocales' => new ArrayCollection([$secondLevelLevelEn])]
        );
        $en = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Locale', [
            'code' => 'en',
            'childLocales' => new ArrayCollection([$firstLevelEn])
        ]);
        $firstLevelRu = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Locale', ['code' => 'ru_RU']);
        $ru = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Locale', [
            'code' => 'ru',
            'childLocales' => new ArrayCollection([$firstLevelRu])
        ]);
        $locales = [
            'en' => ['en_EN' => ['en_FR' => []]],
            'ru' => ['ru_RU' => []],
        ];
        return [
            ['entities' => [$en, $ru], 'locales' => $locales],
        ];
    }

    /**
     * @dataProvider getLocaleFallbacksCacheDataProvider
     *
     * @param array $locales
     */
    public function testGetLocaleFallbacksCache(array $locales)
    {
        $this->cache->expects($this->once())
            ->method('contains')
            ->with(LocaleFallbackStrategy::CACHE_KEY)
            ->willReturn(true);
        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(LocaleFallbackStrategy::CACHE_KEY)
            ->willReturn($locales);
        $this->assertEquals($locales, $this->strategy->getLocaleFallbacks());
    }

    /**
     * @return array
     */
    public function getLocaleFallbacksCacheDataProvider()
    {
        return [
            [
                'locales' => [
                    'en' => ['en_EN' => ['en_FR' => []]],
                    'ru' => ['ru_RU' => []],
                ]
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(LocaleFallbackStrategy::NAME, $this->strategy->getName());
    }

    public function testClearCache()
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with(LocaleFallbackStrategy::CACHE_KEY);
        $this->strategy->clearCache();
    }
}
