<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Entity\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentWidgetRepository;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadContentWidgetData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ContentWidgetRepositoryTest extends WebTestCase
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ContentWidgetRepository */
    private $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadContentWidgetData::class,
            ]
        );

        $this->doctrine = $this->getContainer()->get('doctrine');
        $this->repository = $this->doctrine->getRepository(ContentWidget::class);
    }

    public function testFindAllByNames(): void
    {
        $aclHelper = $this->getContainer()->get('oro_security.acl_helper');

        $this->assertEquals(
            [
                $this->getReference(LoadContentWidgetData::CONTENT_WIDGET_1),
                $this->getReference(LoadContentWidgetData::CONTENT_WIDGET_3)
            ],
            $this->repository->findAllByNames([
                LoadContentWidgetData::CONTENT_WIDGET_1,
                LoadContentWidgetData::CONTENT_WIDGET_3
            ], $aclHelper)
        );
    }

    public function testFindOneByName(): void
    {
        /** @var ContentWidget $expectedContentWidget */
        $expectedContentWidget = $this->repository->findOneBy([]);
        $expectedName = $expectedContentWidget->getName();

        $actualContentWidget = $this->repository
            ->findOneByName($expectedName, $expectedContentWidget->getOrganization());

        $this->assertNotNull($actualContentWidget);
        $this->assertEquals($expectedContentWidget->getId(), $actualContentWidget->getId());
        $this->assertEquals($expectedName, $actualContentWidget->getName());
    }

    public function testFindOneByNameWhenNotExists(): void
    {
        $this->assertNull($this->repository->findOneByName('non-existing', $this->getReference('organization')));
    }
}
