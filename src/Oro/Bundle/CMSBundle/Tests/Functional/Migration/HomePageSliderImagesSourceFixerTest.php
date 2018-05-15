<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Migration;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Migration\HomePageSliderImagesSourceFixer;
use Oro\Bundle\CMSBundle\Migrations\Data\ORM\LoadHomePageSlider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class HomePageSliderImagesSourceFixerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures([]);
    }

    public function testOnInstallationFinishWhenNoHomePageSliderBlockExists()
    {
        $block = $this->getHomePageSliderBlock();

        $entityManager = $this->getDoctrineHelper()->getEntityManager(ContentBlock::class);
        $entityManager->remove($block);
        $entityManager->flush();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No block for home page slider is found');

        $this->getListener()->convertImagesPaths();
    }

    public function testOnInstallationFinishWhenNoSubPathExists()
    {
        $applicationUrl = 'http://myapplication.com/';
        $configManager = static::getContainer()->get('oro_config.global');
        $configManager->set('oro_ui.application_url', $applicationUrl);
        $configManager->flush();

        $block = $this->getHomePageSliderBlock();
        $contentVariant = $this->getDefaultContentVariant($block);
        $html = '<img src="/bundles/somebundle/images/image.jpg" />';
        $contentVariant->setContent($html);

        $this->getDoctrineHelper()->getEntityManager(ContentBlock::class)->flush();
        $this->getDoctrineHelper()->getEntityManager(ContentBlock::class)->getUnitOfWork()->clear();

        $this->getListener()->convertImagesPaths();

        $block = $this->getHomePageSliderBlock();
        $contentVariant = $this->getDefaultContentVariant($block);

        $expectedHtml = '<img src="/bundles/somebundle/images/image.jpg" />';

        $this->assertEquals($expectedHtml, $contentVariant->getContent());
    }

    public function testOnInstallationFinishWhenSubPathExists()
    {
        $applicationUrl = 'https://myapplication.com/some/path';
        $configManager = static::getContainer()->get('oro_config.global');
        $configManager->set('oro_ui.application_url', $applicationUrl);
        $configManager->flush();

        $block = $this->getHomePageSliderBlock();
        $contentVariant = $this->getDefaultContentVariant($block);
        $html = '<img src="/bundles/somebundle/images/image.jpg" /><a href="/link/uri">Link</a>';
        $contentVariant->setContent($html);

        $this->getDoctrineHelper()->getEntityManager(ContentBlock::class)->flush();
        $this->getDoctrineHelper()->getEntityManager(ContentBlock::class)->getUnitOfWork()->clear();

        $this->getListener()->convertImagesPaths();

        $block = $this->getHomePageSliderBlock();
        $contentVariant = $this->getDefaultContentVariant($block);

        $expectedHtml =
            '<img src="/some/path/bundles/somebundle/images/image.jpg" /><a href="/some/path/link/uri">Link</a>';

        $this->assertEquals($expectedHtml, $contentVariant->getContent());
    }

    /**
     * @return ContentBlock
     */
    private function getHomePageSliderBlock()
    {
        $repository = $this->getDoctrineHelper()->getEntityRepository(ContentBlock::class);

        return $repository->findOneBy(['alias' => LoadHomePageSlider::HOME_PAGE_SLIDER_ALIAS]);
    }

    /**
     * @param ContentBlock $block
     * @return \Oro\Bundle\CMSBundle\Entity\TextContentVariant
     */
    private function getDefaultContentVariant(ContentBlock $block)
    {
        foreach ($block->getContentVariants() as $contentVariant) {
            if ($contentVariant->isDefault()) {
                return $contentVariant;
            }
        }
    }

    /**
     * @return DoctrineHelper
     */
    private function getDoctrineHelper()
    {
        return static::getContainer()->get('oro_entity.doctrine_helper');
    }

    /**
     * @return HomePageSliderImagesSourceFixer
     */
    private function getListener()
    {
        return static::getContainer()->get('oro_cms.migration.home_page_slider_images_source_fixer');
    }
}
