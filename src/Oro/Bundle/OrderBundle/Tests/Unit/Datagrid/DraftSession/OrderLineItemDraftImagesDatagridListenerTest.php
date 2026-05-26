<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Datagrid\DraftSession;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProviderInterface;
use Oro\Bundle\OrderBundle\Datagrid\DraftSession\OrderLineItemDraftImagesDatagridListener;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderLineItemDraftImagesDatagridListenerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private SelectedFieldsProviderInterface&MockObject $selectedFieldsProvider;
    private OrderLineItemDraftImagesDatagridListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->selectedFieldsProvider = $this->createMock(SelectedFieldsProviderInterface::class);

        $this->listener = new OrderLineItemDraftImagesDatagridListener(
            $this->doctrine,
            $this->selectedFieldsProvider
        );
    }

    public function testOnResultAfterWhenProductFieldNotSelected(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = $this->createMock(DatagridConfiguration::class);
        $parameters = new ParameterBag();

        $datagrid
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $datagrid
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameters);

        $this->selectedFieldsProvider
            ->expects(self::once())
            ->method('getSelectedFields')
            ->with($config, $parameters)
            ->willReturn(['id', 'sku']);

        $event = new OrmResultAfter($datagrid, []);

        $this->doctrine
            ->expects(self::never())
            ->method('getRepository');

        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfterWhenNoRecords(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = $this->createMock(DatagridConfiguration::class);
        $parameters = new ParameterBag();

        $datagrid
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $datagrid
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameters);

        $this->selectedFieldsProvider
            ->expects(self::once())
            ->method('getSelectedFields')
            ->with($config, $parameters)
            ->willReturn(['product']);

        $event = new OrmResultAfter($datagrid, []);

        $this->doctrine
            ->expects(self::never())
            ->method('getRepository');

        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfterAddsListingAndMainImages(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = $this->createMock(DatagridConfiguration::class);
        $parameters = new ParameterBag();

        $datagrid
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $datagrid
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameters);

        $this->selectedFieldsProvider
            ->expects(self::once())
            ->method('getSelectedFields')
            ->with($config, $parameters)
            ->willReturn(['product']);

        $product1 = new ProductStub();
        $product1->setId(1);

        $product2 = new ProductStub();
        $product2->setId(2);

        $lineItem1 = new OrderLineItem();
        $lineItem1->setProduct($product1);

        $lineItem2 = new OrderLineItem();
        $lineItem2->setProduct($product2);

        $record1 = new ResultRecord(['id' => 101, $lineItem1]);
        $record2 = new ResultRecord(['id' => 102, $lineItem2]);

        $listingImage1 = new File();
        $mainImage1 = new File();
        $listingImage2 = new File();

        $images = [
            1 => [
                ProductImageType::TYPE_LISTING => $listingImage1,
                ProductImageType::TYPE_MAIN => $mainImage1,
            ],
            2 => [
                ProductImageType::TYPE_LISTING => $listingImage2,
            ],
        ];

        $repository = $this->createMock(ProductRepository::class);
        $repository
            ->expects(self::once())
            ->method('getListingAndMainImagesFilesByProductIds')
            ->with([1, 2])
            ->willReturn($images);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $event = new OrmResultAfter($datagrid, [$record1, $record2]);

        $this->listener->onResultAfter($event);

        self::assertSame($listingImage1, $record1->getValue('productImageListing'));
        self::assertSame($mainImage1, $record1->getValue('productImageMain'));
        self::assertSame($listingImage2, $record2->getValue('productImageListing'));
        self::assertNull($record2->getValue('productImageMain'));
    }

    public function testOnResultAfterSkipsLineItemsWithoutProducts(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = $this->createMock(DatagridConfiguration::class);
        $parameters = new ParameterBag();

        $datagrid
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $datagrid
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameters);

        $this->selectedFieldsProvider
            ->expects(self::once())
            ->method('getSelectedFields')
            ->with($config, $parameters)
            ->willReturn(['product']);

        $product1 = new ProductStub();
        $product1->setId(1);

        $lineItem1 = new OrderLineItem();
        $lineItem1->setProduct($product1);

        $lineItem2 = new OrderLineItem();

        $record1 = new ResultRecord(['id' => 101, $lineItem1]);
        $record2 = new ResultRecord(['id' => 102, $lineItem2]);

        $listingImage1 = new File();

        $images = [
            1 => [
                ProductImageType::TYPE_LISTING => $listingImage1,
            ],
        ];

        $repository = $this->createMock(ProductRepository::class);
        $repository
            ->expects(self::once())
            ->method('getListingAndMainImagesFilesByProductIds')
            ->with([1])
            ->willReturn($images);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $event = new OrmResultAfter($datagrid, [$record1, $record2]);

        $this->listener->onResultAfter($event);

        self::assertSame($listingImage1, $record1->getValue('productImageListing'));
        self::assertNull($record2->getValue('productImageListing'));
        self::assertNull($record2->getValue('productImageMain'));
    }

    public function testOnResultAfterHandlesProductsWithoutImages(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = $this->createMock(DatagridConfiguration::class);
        $parameters = new ParameterBag();

        $datagrid
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $datagrid
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameters);

        $this->selectedFieldsProvider
            ->expects(self::once())
            ->method('getSelectedFields')
            ->with($config, $parameters)
            ->willReturn(['product']);

        $product1 = new ProductStub();
        $product1->setId(1);

        $lineItem1 = new OrderLineItem();
        $lineItem1->setProduct($product1);

        $record1 = new ResultRecord(['id' => 101, $lineItem1]);

        $repository = $this->createMock(ProductRepository::class);
        $repository
            ->expects(self::once())
            ->method('getListingAndMainImagesFilesByProductIds')
            ->with([1])
            ->willReturn([]);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $event = new OrmResultAfter($datagrid, [$record1]);

        $this->listener->onResultAfter($event);

        self::assertNull($record1->getValue('productImageListing'));
        self::assertNull($record1->getValue('productImageMain'));
    }

    public function testOnResultAfterHandlesDuplicateProductIds(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = $this->createMock(DatagridConfiguration::class);
        $parameters = new ParameterBag();

        $datagrid
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $datagrid
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameters);

        $this->selectedFieldsProvider
            ->expects(self::once())
            ->method('getSelectedFields')
            ->with($config, $parameters)
            ->willReturn(['product']);

        $product1 = new ProductStub();
        $product1->setId(1);

        $lineItem1 = new OrderLineItem();
        $lineItem1->setProduct($product1);

        $lineItem2 = new OrderLineItem();
        $lineItem2->setProduct($product1);

        $record1 = new ResultRecord(['id' => 101, $lineItem1]);
        $record2 = new ResultRecord(['id' => 102, $lineItem2]);

        $listingImage = new File();

        $images = [
            1 => [
                ProductImageType::TYPE_LISTING => $listingImage,
            ],
        ];

        $repository = $this->createMock(ProductRepository::class);
        $repository
            ->expects(self::once())
            ->method('getListingAndMainImagesFilesByProductIds')
            ->with([1, 1])
            ->willReturn($images);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $event = new OrmResultAfter($datagrid, [$record1, $record2]);

        $this->listener->onResultAfter($event);

        self::assertSame($listingImage, $record1->getValue('productImageListing'));
        self::assertSame($listingImage, $record2->getValue('productImageListing'));
    }

    public function testOnResultAfterHandlesOnlyListingImage(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = $this->createMock(DatagridConfiguration::class);
        $parameters = new ParameterBag();

        $datagrid
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $datagrid
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameters);

        $this->selectedFieldsProvider
            ->expects(self::once())
            ->method('getSelectedFields')
            ->with($config, $parameters)
            ->willReturn(['product']);

        $product1 = new ProductStub();
        $product1->setId(1);

        $lineItem1 = new OrderLineItem();
        $lineItem1->setProduct($product1);

        $record1 = new ResultRecord(['id' => 101, $lineItem1]);

        $listingImage = new File();

        $images = [
            1 => [
                ProductImageType::TYPE_LISTING => $listingImage,
            ],
        ];

        $repository = $this->createMock(ProductRepository::class);
        $repository
            ->expects(self::once())
            ->method('getListingAndMainImagesFilesByProductIds')
            ->with([1])
            ->willReturn($images);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $event = new OrmResultAfter($datagrid, [$record1]);

        $this->listener->onResultAfter($event);

        self::assertSame($listingImage, $record1->getValue('productImageListing'));
        self::assertNull($record1->getValue('productImageMain'));
    }

    public function testOnResultAfterHandlesOnlyMainImage(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = $this->createMock(DatagridConfiguration::class);
        $parameters = new ParameterBag();

        $datagrid
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $datagrid
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameters);

        $this->selectedFieldsProvider
            ->expects(self::once())
            ->method('getSelectedFields')
            ->with($config, $parameters)
            ->willReturn(['product']);

        $product1 = new ProductStub();
        $product1->setId(1);

        $lineItem1 = new OrderLineItem();
        $lineItem1->setProduct($product1);

        $record1 = new ResultRecord(['id' => 101, $lineItem1]);

        $mainImage = new File();

        $images = [
            1 => [
                ProductImageType::TYPE_MAIN => $mainImage,
            ],
        ];

        $repository = $this->createMock(ProductRepository::class);
        $repository
            ->expects(self::once())
            ->method('getListingAndMainImagesFilesByProductIds')
            ->with([1])
            ->willReturn($images);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $event = new OrmResultAfter($datagrid, [$record1]);

        $this->listener->onResultAfter($event);

        self::assertNull($record1->getValue('productImageListing'));
        self::assertSame($mainImage, $record1->getValue('productImageMain'));
    }
}
