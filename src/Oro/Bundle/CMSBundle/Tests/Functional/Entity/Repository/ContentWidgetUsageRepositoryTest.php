<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidgetUsage;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentWidgetUsageRepository;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadContentWidgetData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ContentWidgetUsageRepositoryTest extends WebTestCase
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ContentWidgetUsageRepository
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadContentWidgetData::class]);

        $this->registry = $this->getContainer()->get('doctrine');
        $this->repository = $this->registry->getRepository(ContentWidgetUsage::class);
    }

    public function testAddAndRemove(): void
    {
        $this->assertCount(2, $this->repository->findBy([]));

        $this->repository->add(\stdClass::class, 42, $this->getReference(LoadContentWidgetData::CONTENT_WIDGET_1));
        $this->repository->add(\stdClass::class, 42, $this->getReference(LoadContentWidgetData::CONTENT_WIDGET_2));
        $this->repository->add(\stdClass::class, 42, $this->getReference(LoadContentWidgetData::CONTENT_WIDGET_3));

        $this->assertCount(5, $this->repository->findBy([]));

        $this->repository->remove(\stdClass::class, 42, $this->getReference(LoadContentWidgetData::CONTENT_WIDGET_2));

        $this->assertCount(4, $this->repository->findBy([]));

        $this->repository->remove(\stdClass::class, 42);

        $this->assertCount(2, $this->repository->findBy([]));
    }
}
