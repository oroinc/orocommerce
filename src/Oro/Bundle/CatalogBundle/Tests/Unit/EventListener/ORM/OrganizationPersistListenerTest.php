<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\EventListener\ORM\OrganizationPersistListener;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationPersistListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrganizationPersistListener */
    private $listener;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->listener = new OrganizationPersistListener($this->doctrineHelper);
    }

    public function testPostPersist()
    {
        $organization = new Organization();

        $manager = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with(Category::class)
            ->willReturn($manager);
        $manager->expects($this->once())->method('persist')->with($this->callback(
            function ($class) use ($organization) {
                $this->assertInstanceOf(Category::class, $class);
                /** @var Category $class */
                $this->assertSame($organization, $class->getOrganization());
                $this->assertCount(1, $class->getTitles());
                $this->assertEquals(
                    OrganizationPersistListener::ROOT_CATEGORY_NAME,
                    $class->getTitles()->first()->getString()
                );

                return true;
            }
        ));
        $this->listener->prePersist($organization);
    }
}
