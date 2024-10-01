<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Helper\FieldConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductMultiFieldsDuplicateListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub as Product;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class ProductMultiFieldsDuplicateListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductMultiFieldsDuplicateListener */
    private $listener;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->listener = new ProductMultiFieldsDuplicateListener($this->configProvider, $this->managerRegistry);
    }

    public function testOnDuplicateAfter(): void
    {
        $config = new FieldConfigId('', Product::class, 'externalField', FieldConfigHelper::MULTI_FILE_TYPE);
        $this->configProvider
            ->expects(self::once())
            ->method('getIds')
            ->willReturn([$config]);

        $manager = $this->createMock(EntityManager::class);
        $manager
            ->expects(self::once())
            ->method('persist');
        $manager
            ->expects(self::once())
            ->method('flush');

        $this->managerRegistry
            ->expects(self::exactly(2))
            ->method('getManager')
            ->willReturn($manager);

        $sourceProduct = new Product();
        $targetProduct = clone $sourceProduct;

        $file = new File();
        $fileItem = new FileItem();
        $fileItem->setFile($file);

        $sourceProduct->set('externalField', new ArrayCollection([$fileItem]));
        $event = new ProductDuplicateAfterEvent($sourceProduct, $targetProduct);
        $this->listener->onDuplicateAfter($event);

        $expectedFileType = $sourceProduct->get('externalField')->first();
        self::assertNotEquals(spl_object_hash($expectedFileType->getFile()), spl_object_hash($file));
    }
}
