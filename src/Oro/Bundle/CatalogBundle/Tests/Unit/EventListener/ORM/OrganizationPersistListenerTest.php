<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\EventListener\ORM\OrganizationPersistListener;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationPersistListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrganizationPersistListener */
    private $listener;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->listener = new OrganizationPersistListener($this->doctrineHelper);
    }

    public function testPostPersist()
    {
        $organization = new Organization();
        $title = new LocalizedFallbackValue();
        $title->setString(OrganizationPersistListener::ROOT_CATEGORY_NAME);
        $expectedCategoryCreated = (new Category())
            ->setOrganization($organization)
            ->addTitle($title);

        $manager = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with(Category::class)
            ->willReturn($manager);
        $manager->expects($this->once())->method('persist')->with($this->callback(
            function ($class) use ($expectedCategoryCreated) {
                /** @var $class Category */
                $this->assertSame($expectedCategoryCreated->getOrganization(), $class->getOrganization());
                $this->assertEquals($expectedCategoryCreated->getTitles(), $class->getTitles());
                return true;
            }
        ));
        $this->listener->prePersist($organization);
    }
}
