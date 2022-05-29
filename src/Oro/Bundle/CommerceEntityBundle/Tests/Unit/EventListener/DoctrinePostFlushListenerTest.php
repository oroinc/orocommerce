<?php

namespace Oro\Bundle\CommerceEntityBundle\Tests\Unit\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CommerceEntityBundle\EventListener\DoctrinePostFlushListener;
use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorage;
use Oro\Bundle\CommerceEntityBundle\Tests\Stub\Entity1;
use Oro\Bundle\CommerceEntityBundle\Tests\Stub\Entity2;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class DoctrinePostFlushListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testPostFlush()
    {
        $doctrine = $this->createMock(ManagerRegistry::class);

        $testEntity1 = new Entity1();
        $testEntity2 = new Entity1();
        $testEntity3 = new Entity2();

        $storage = new ExtraActionEntityStorage();
        $storage->scheduleForExtraInsert($testEntity1);
        $storage->scheduleForExtraInsert($testEntity2);
        $storage->scheduleForExtraInsert($testEntity3);

        $em1 = $this->createMock(EntityManager::class);
        $em2 = $this->createMock(EntityManager::class);

        //method 'persist' should be called once for every entity
        $em1->expects($this->exactly(2))
            ->method('persist')
            ->withConsecutive([$testEntity1], [$testEntity2]);
        $em2->expects($this->once())
            ->method('persist')
            ->with($testEntity3);

        //method 'flush' should be called only once for every manager
        $em1->expects($this->once())
            ->method('flush');
        $em2->expects($this->once())
            ->method('flush');

        $doctrine->expects($this->exactly(3))
            ->method('getManagerForClass')
            ->willReturnMap([
                [ClassUtils::getClass($testEntity1), $em1],
                [ClassUtils::getClass($testEntity2), $em1],
                [ClassUtils::getClass($testEntity3), $em2]
            ]);

        $doctrineHelper = $this->getDoctrineHelper($doctrine);
        $listener = new DoctrinePostFlushListener($doctrineHelper, $storage);
        $listener->postFlush();
        $this->assertEmpty($storage->getScheduledForInsert());
    }

    public function testPostFlushDisabled()
    {
        $testEntity = new Entity1();
        $storage = new ExtraActionEntityStorage();
        $storage->scheduleForExtraInsert($testEntity);

        $listener = new DoctrinePostFlushListener($this->getDoctrineHelper(), $storage);
        $listener->setEnabled(false);
        $listener->postFlush();
        $this->assertNotEmpty($storage->getScheduledForInsert());
    }

    private function getDoctrineHelper(ManagerRegistry $doctrine = null): DoctrineHelper
    {
        return new DoctrineHelper($doctrine ?: $this->createMock(ManagerRegistry::class));
    }
}
