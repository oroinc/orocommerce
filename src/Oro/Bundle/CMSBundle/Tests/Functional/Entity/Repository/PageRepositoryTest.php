<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Entity\Repository\PageRepository;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class PageRepositoryTest extends WebTestCase
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var PageRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->registry = $this->getContainer()->get('doctrine');
        $this->repository = $this->registry->getRepository(Page::class);
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
}
