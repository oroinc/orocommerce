<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\WYSIWYG;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\Repository\FileRepository;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Tests\Functional\DataFixtures\LoadDigitalAssetData;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DigitalAssetTwigFunctionProcessorTest extends WebTestCase
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var FileRepository */
    private $fileRepository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadDigitalAssetData::class]);

        $this->getOptionalListenerManager()->enableListener('oro_cms.event_listener.wysiwyg_field_twig_listener');

        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->fileRepository = $this->entityManager->getRepository(File::class);
    }

    public function testPostPersist(): Page
    {
        // Cleanup
        foreach ($this->fileRepository->findBy(['parentEntityClass' => Page::class]) as $file) {
            $this->entityManager->remove($file);
        }
        $this->entityManager->flush();

        /** @var DigitalAsset $digitalAsset1 */
        $digitalAsset1 = $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_1);
        $digitalAsset1Id = $digitalAsset1->getId();

        /** @var DigitalAsset $digitalAsset2 */
        $digitalAsset2 = $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_2);
        $digitalAsset2Id = $digitalAsset2->getId();

        $digitalAsset1ContentUUID = UUIDGenerator::v4();
        $digitalAsset1StyleUUID = UUIDGenerator::v4();

        $digitalAsset2ContentUUID = UUIDGenerator::v4();

        $page = new Page();
        $page->setDefaultTitle('testTitle');
        $page->setContent(
            "<div class=\"test\">
                <img src=\"{{ wysiwyg_image({$digitalAsset1Id}, '{$digitalAsset1ContentUUID}',"
            . " 'wysiwyg_original', '') }}\"/>"
            . "<a href=\"{{ wysiwyg_file({$digitalAsset2Id}, '{$digitalAsset2ContentUUID}') }}\">
                    Download
                </a>
            </div>"
        );

        $page->setContentStyle(
            ".test {
                background-image: url({{ wysiwyg_image({$digitalAsset1Id}, '{$digitalAsset1StyleUUID}', "
            . "'wysiwyg_original', '')}})
            }"
        );

        $this->entityManager->persist($page);

        $this->assertDigitalAssets($page, [
            $digitalAsset1ContentUUID => $digitalAsset1,
            $digitalAsset2ContentUUID => $digitalAsset2,
        ], [
            $digitalAsset1StyleUUID => $digitalAsset1,
        ]);

        return $page;
    }

    /**
     * @depends testPostPersist
     */
    public function testPreUpdate(Page $page): Page
    {
        /** @var DigitalAsset $digitalAsset2 */
        $digitalAsset2 = $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_2);
        $digitalAsset2Id = $digitalAsset2->getId();

        // Not change uuid for file 2
        /** @var File $file */
        $file = $this->fileRepository->findOneBy([
            'parentEntityClass' => $this->entityManager->getClassMetadata(\get_class($page))->getName(),
            'digitalAsset' => $digitalAsset2,
        ]);
        $digitalAsset2ContentUUID = $file->getUuid();

        /** @var DigitalAsset $digitalAsset3 */
        $digitalAsset3 = $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_3);
        $digitalAsset3Id = $digitalAsset3->getId();

        $digitalAsset3ContentUUID = UUIDGenerator::v4();
        $digitalAsset3StyleUUID = UUIDGenerator::v4();

        $page->setContent(
            "<div class=\"test\">
                <img src=\"{{ wysiwyg_image({$digitalAsset3Id}, '{$digitalAsset3ContentUUID}', "
            . "'wysiwyg_original', '') }}\"/>
                <a href=\"{{ wysiwyg_file({$digitalAsset2Id}, '{$digitalAsset2ContentUUID}') }}\">
                    Download
                </a>
            </div>"
        );

        $page->setContentStyle(
            ".test {
                background-image: url({{ wysiwyg_image({$digitalAsset3Id}, '{$digitalAsset3StyleUUID}', "
            . "'wysiwyg_original', '')}})
            }"
        );

        $this->assertDigitalAssets($page, [
            $digitalAsset2ContentUUID => $digitalAsset2,
            $digitalAsset3ContentUUID => $digitalAsset3,
        ], [
            $digitalAsset3StyleUUID => $digitalAsset3,
        ]);

        return $page;
    }

    /**
     * @depends testPreUpdate
     */
    public function testRemove(Page $page): void
    {
        $this->entityManager->remove($page);

        $this->assertDigitalAssets($page, [], []);
    }

    /**
     * @param Page $page
     * @param DigitalAsset[] $contentDigitalAssets
     * @param DigitalAsset[] $styleDigitalAssets
     */
    private function assertDigitalAssets(Page $page, array $contentDigitalAssets, array $styleDigitalAssets): void
    {
        $this->entityManager->flush();
        self::getContainer()->get('oro_cms.tests.event_listener.wysiwyg_field_twig_listener')->onTerminate();

        /** @var File[] $files */
        $files = $this->fileRepository->findBy([
            'parentEntityClass' => $this->entityManager->getClassMetadata(\get_class($page))->getName(),
        ]);

        self::assertCount(\count($contentDigitalAssets) + \count($styleDigitalAssets), $files);

        foreach ($files as $file) {
            self::assertSame(Page::class, $file->getParentEntityClass());
            self::assertSame($page->getId(), $file->getParentEntityId());
            self::assertNotNull($file->getUuid());

            switch ($file->getParentEntityFieldName()) {
                case 'content':
                    self::assertSame('content', $file->getParentEntityFieldName());

                    self::assertArrayHasKey($file->getUuid(), $contentDigitalAssets);
                    self::assertEquals($contentDigitalAssets[$file->getUuid()], $file->getDigitalAsset());
                    unset($contentDigitalAssets[$file->getUuid()]);
                    break;

                case 'style':
                    self::assertSame('content_style', $file->getParentEntityFieldName());

                    self::assertArrayHasKey($file->getUuid(), $styleDigitalAssets);
                    self::assertEquals($styleDigitalAssets[$file->getUuid()], $file->getDigitalAsset());
                    unset($styleDigitalAssets[$file->getUuid()]);
                    break;
            }
        }
    }
}
