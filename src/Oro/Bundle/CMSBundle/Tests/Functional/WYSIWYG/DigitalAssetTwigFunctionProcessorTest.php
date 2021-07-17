<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\WYSIWYG;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\Repository\FileRepository;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Tests\Functional\DataFixtures\LoadDigitalAssetData;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DigitalAssetTwigFunctionProcessorTest extends WebTestCase
{
    /** @var ObjectManager */
    private $em;

    /** @var FileRepository */
    private $fileRepository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadDigitalAssetData::class]);

        $this->getOptionalListenerManager()->enableListener('oro_cms.event_listener.wysiwyg_field_twig_listener');

        /** @var Registry */
        $doctrine = $this->getContainer()->get('doctrine');

        $this->em = $doctrine->getManager();
        $this->fileRepository = $this->em->getRepository(File::class);
    }

    public function testPostPersist(): Page
    {
        //cleanup
        foreach ($this->fileRepository->findBy(['parentEntityClass' => Page::class]) as $file) {
            $this->em->remove($file);
        }
        $this->em->flush();

        /** @var ContentWidget $digitalAsset1 */
        $digitalAsset1 = $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_1);

        /** @var ContentWidget $digitalAsset2 */
        $digitalAsset2 = $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_2);

        $digitalAsset1_content_UUID = UUIDGenerator::v4();
        $digitalAsset1_style_UUID = UUIDGenerator::v4();

        $digitalAsset2_content_UUID = UUIDGenerator::v4();

        $page = new Page();
        $page->setDefaultTitle('testTitle');
        $page->setContent(
            "<div class=\"test\">
                <img src=\"{{ wysiwyg_image(" . $digitalAsset1->getId() . ", '{$digitalAsset1_content_UUID}') }}\"/>
                <a href=\"{{ wysiwyg_file(" . $digitalAsset2->getId() . ", '{$digitalAsset2_content_UUID}') }}\">
                    Download
                </a>
            </div>"
        );

        $page->setContentStyle(
            ".test {
                backgroud-image: url({{ wysiwyg_image(" . $digitalAsset1->getId() . ", '{$digitalAsset1_style_UUID}')}})
            }"
        );

        $this->em->persist($page);

        $this->assertDigitalAssets($page, [
            $digitalAsset1_content_UUID => $digitalAsset1,
            $digitalAsset2_content_UUID => $digitalAsset2,
        ], [
            $digitalAsset1_style_UUID => $digitalAsset1,
        ]);

        return $page;
    }

    /**
     * @depends testPostPersist
     */
    public function testPreUpdate(Page $page): Page
    {
        /** @var ContentWidget $digitalAsset2 */
        $digitalAsset2 = $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_2);

        // Not change uuid for file 2
        /** @var File $file */
        $file = $this->fileRepository->findOneBy([
            'parentEntityClass' => $this->em->getClassMetadata(\get_class($page))->getName(),
            'digitalAsset' => $digitalAsset2,
        ]);
        $digitalAsset2_content_UUID = $file->getUuid();

        /** @var ContentWidget $digitalAsset3 */
        $digitalAsset3 = $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_3);

        $digitalAsset3_content_UUID = UUIDGenerator::v4();
        $digitalAsset3_style_UUID = UUIDGenerator::v4();

        $page->setContent(
            "<div class=\"test\">
                <img src=\"{{ wysiwyg_image(" . $digitalAsset3->getId() . ", '{$digitalAsset3_content_UUID}') }}\"/>
                <a href=\"{{ wysiwyg_file(" . $digitalAsset2->getId() . ", '{$digitalAsset2_content_UUID}') }}\">
                    Download
                </a>
            </div>"
        );

        $page->setContentStyle(
            ".test {
                backgroud-image: url({{ wysiwyg_image(" . $digitalAsset3->getId() . ", '{$digitalAsset3_style_UUID}')}})
            }"
        );

        $this->assertDigitalAssets($page, [
            $digitalAsset2_content_UUID => $digitalAsset2,
            $digitalAsset3_content_UUID => $digitalAsset3,
        ], [
            $digitalAsset3_style_UUID => $digitalAsset3,
        ]);

        return $page;
    }

    /**
     * @depends testPreUpdate
     */
    public function testRemove(Page $page): void
    {
        $this->em->remove($page);

        $this->assertDigitalAssets($page, [], []);
    }

    /**
     * @param Page $page
     * @param DigitalAsset[] $contentDigitalAssets
     * @param DigitalAsset[] $styleDigitalAssets
     */
    private function assertDigitalAssets(Page $page, array $contentDigitalAssets, array $styleDigitalAssets): void
    {
        $this->em->flush();
        $this->getContainer()->get('oro_cms.tests.event_listener.wysiwyg_field_twig_listener')->onTerminate();

        /** @var File[] $files */
        $files = $this->fileRepository->findBy([
            'parentEntityClass' => $this->em->getClassMetadata(\get_class($page))->getName(),
        ]);

        $this->assertCount(\count($contentDigitalAssets) + \count($styleDigitalAssets), $files);

        foreach ($files as $file) {
            $this->assertSame(Page::class, $file->getParentEntityClass());
            $this->assertSame($page->getId(), $file->getParentEntityId());
            $this->assertNotNull($file->getUuid());

            switch ($file->getParentEntityFieldName()) {
                case 'content':
                    $this->assertSame('content', $file->getParentEntityFieldName());

                    $this->assertArrayHasKey($file->getUuid(), $contentDigitalAssets);
                    $this->assertEquals($contentDigitalAssets[$file->getUuid()], $file->getDigitalAsset());
                    unset($contentDigitalAssets[$file->getUuid()]);
                    break;

                case 'style':
                    $this->assertSame('content_style', $file->getParentEntityFieldName());

                    $this->assertArrayHasKey($file->getUuid(), $styleDigitalAssets);
                    $this->assertEquals($styleDigitalAssets[$file->getUuid()], $file->getDigitalAsset());
                    unset($styleDigitalAssets[$file->getUuid()]);
                    break;
            }
        }
    }
}
