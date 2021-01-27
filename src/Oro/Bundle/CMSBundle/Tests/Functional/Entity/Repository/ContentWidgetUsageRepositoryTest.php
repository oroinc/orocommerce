<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Entity\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidgetUsage;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentWidgetUsageRepository;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadContentWidgetUsageData;
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
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadContentWidgetUsageData::class]);

        $this->registry = $this->getContainer()->get('doctrine');
        $this->repository = $this->registry->getRepository(ContentWidgetUsage::class);
    }

    public function testFindForEntityField(): void
    {
        /** @var ContentWidgetUsage $usage1a */
        $usage1a = $this->getReference(LoadContentWidgetUsageData::CONTENT_WIDGET_USAGE_1_A);

        /** @var ContentWidgetUsage $usage1b */
        $usage1b = $this->getReference(LoadContentWidgetUsageData::CONTENT_WIDGET_USAGE_1_B);

        $this->assertSame(
            [
                $usage1a,
                $usage1b,
            ],
            $this->repository->findForEntityField(
                $usage1a->getEntityClass(),
                $usage1a->getEntityId()
            )
        );

        $this->assertSame(
            [
                $usage1a,
            ],
            $this->repository->findForEntityField(
                $usage1a->getEntityClass(),
                $usage1a->getEntityId(),
                $usage1a->getEntityField()
            )
        );
    }
}
