<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentWidgetRepository;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadContentWidgetData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ContentWidgetRepositoryTest extends WebTestCase
{
    /** @var RegistryInterface */
    private $doctrine;

    /** @var ContentWidgetRepository */
    private $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
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
