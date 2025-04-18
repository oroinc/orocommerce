<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Entity\Repository\PageRepository;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class PageRepositoryTest extends WebTestCase
{
    private PageRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadPageData::class]);

        $this->repository = self::getContainer()->get('doctrine')->getRepository(Page::class);
    }

    public function testFindOneByDefaultTitle()
    {
        /** @var Page $expectedPage */
        $expectedPage = $this->repository->findOneBy([]);
        $expectedTitle = $expectedPage->getDefaultTitle()->getString();

        $actualPage = $this->repository->findOneByTitle($expectedTitle);
        $this->assertInstanceOf(Page::class, $actualPage);
        $this->assertEquals($expectedPage->getId(), $actualPage->getId());
        $this->assertEquals($expectedTitle, $actualPage->getDefaultTitle()->getString());

        $this->assertNull($this->repository->findOneByTitle('Not existing Page'));
    }

    public function testFindOneByTitle()
    {
        /** @var Page $expectedPage */
        $expectedPage = $this->repository->findOneBy([]);
        /** @var LocalizedFallbackValue $title */
        $title = $expectedPage->getTitles()->first();

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Localization::class);
        $localization = $em->getRepository(Localization::class)->findOneBy([]);

        $title->setLocalization($localization);
        $em->flush($title);

        $actualPage = $this->repository->findOneByTitle($title->getString(), $localization);
        $this->assertInstanceOf(Page::class, $actualPage);
        $this->assertEquals($expectedPage->getId(), $actualPage->getId());
        $this->assertEquals($title->getString(), $actualPage->getTitle($localization)->getString());
    }

    /**
     * @dataProvider getNonExistentPageIdsProvider
     */
    public function testGetNonExistentPageIds(
        callable $checkedPageIdsCallback,
        array $expectedNonExistentPageIds
    ) {
        $checkedPageIds = $checkedPageIdsCallback();
        $actualNonExistentPageIds = $this->repository->getNonExistentPageIds($checkedPageIds);

        $this->assertArrayIntersectEquals(
            $expectedNonExistentPageIds,
            array_values($actualNonExistentPageIds)
        );
    }

    public function getNonExistentPageIdsProvider(): array
    {
        return [
            'No page ids' => [
                'checkedPageIdsCallback' => function () {
                    return [];
                },
                'expectedNonExistentPageIds' => [],
            ],
            'No removed page ids' => [
                'checkedPageIdsCallback' => function () {
                    return [
                        $this->getReference(LoadPageData::PAGE_2)->getId(),
                        $this->getReference(LoadPageData::PAGE_1)->getId()
                    ];
                },
                'expectedNonExistentPageIds' => [],
            ],
            'Several removed page ids' => [
                'checkedPageIdsCallback' => function () {
                    return [
                        $this->getReference(LoadPageData::PAGE_2)->getId(),
                        $this->getReference(LoadPageData::PAGE_1)->getId(),
                        self::BIGINT - 1,
                        self::BIGINT
                    ];
                },
                'expectedNonExistentPageIds' => [
                    self::BIGINT - 1,
                    self::BIGINT
                ],
            ],
        ];
    }
}
