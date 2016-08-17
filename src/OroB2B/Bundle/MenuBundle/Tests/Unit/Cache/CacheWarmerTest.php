<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit\Cache;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\MenuBundle\Cache\CacheWarmer;
use OroB2B\Bundle\MenuBundle\Menu\DatabaseMenuProvider;
use OroB2B\Bundle\MenuBundle\Tests\Unit\Entity\Stub\MenuItem;

class CacheWarmerTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'FooBundle:BarEntity';

    /**
     * @var CacheWarmer
     */
    protected $warmer;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var DatabaseMenuProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $menuProvider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->menuProvider = $this->getMockBuilder('OroB2B\Bundle\MenuBundle\Menu\DatabaseMenuProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->warmer = new CacheWarmer($this->doctrineHelper, $this->menuProvider);
        $this->warmer->setEntityClass(self::ENTITY_CLASS);
    }

    public function testWarmUp()
    {
        $menus = [
            (new MenuItem())->setDefaultTitle('first_menu'),
            (new MenuItem())->setDefaultTitle('second_menu'),
        ];
        $repo = $this->getMockBuilder('OroB2B\Bundle\MenuBundle\Entity\Repository\MenuItemRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(self::ENTITY_CLASS)
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('findRoots')
            ->willReturn($menus);

        $this->menuProvider->expects($this->exactly(2))
            ->method('rebuildCacheByAlias')
            ->withConsecutive(
                ['first_menu'],
                ['second_menu']
            );

        $this->warmer->warmUp(null);
    }

    public function testIsOptional()
    {
        $this->assertTrue($this->warmer->isOptional());
    }
}
