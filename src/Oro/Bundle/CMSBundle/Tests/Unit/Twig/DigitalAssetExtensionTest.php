<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlByUuidProvider;
use Oro\Bundle\CMSBundle\Twig\DigitalAssetExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class DigitalAssetExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var FileUrlByUuidProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fileUrlByUuidProvider;

    /** @var DigitalAssetExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->fileUrlByUuidProvider = $this->createMock(FileUrlByUuidProvider::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $container = self::getContainerBuilder()
            ->add(FileUrlByUuidProvider::class, $this->fileUrlByUuidProvider)
            ->getContainer($this);

        $this->extension = new DigitalAssetExtension($container);
    }

    public function testGetWysiwygImageUrl()
    {
        $this->fileUrlByUuidProvider->expects($this->once())
            ->method('getFilteredImageUrl')
            ->with('file-uuid', 'test-filter', 1)
            ->willReturn('/url');

        $this->assertSame(
            '/url',
            self::callTwigFunction($this->extension, 'wysiwyg_image', [42, 'file-uuid', 'test-filter'])
        );
    }

    public function testGetWysiwygFileUrl()
    {
        $this->fileUrlByUuidProvider->expects($this->once())
            ->method('getFileUrl')
            ->with('file-uuid', 'download', 1)
            ->willReturn('/url');

        $this->assertSame(
            '/url',
            self::callTwigFunction($this->extension, 'wysiwyg_file', [42, 'file-uuid'])
        );
    }
}
