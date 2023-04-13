<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension\AbstractProductDataStorageExtensionTestCase;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Form\Extension\RequestDataStorageExtension;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType;
use Oro\Bundle\RFPBundle\Provider\ProductAvailabilityProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class RequestDataStorageExtensionTest extends AbstractProductDataStorageExtensionTestCase
{
    /** @var ProductAvailabilityProvider|MockObject */
    private $productAvailabilityProvider;

    /** @var Environment|MockObject */
    private $twig;

    /** @var FlashBagInterface|MockObject */
    private $flashBag;

    /** @var RFPRequest */
    private $entity;

    /** @var RequestDataStorageExtension */
    protected $extension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entity = new RFPRequest();

        $this->productAvailabilityProvider = $this->createMock(ProductAvailabilityProvider::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static fn ($key) => $key . '_translated');

        $this->twig = $this->createMock(Environment::class);
        $this->flashBag = $this->createMock(FlashBagInterface::class);

        $session = $this->createMock(Session::class);
        $session->expects($this->any())
            ->method('getFlashBag')
            ->willReturn($this->flashBag);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);
        $requestStack->expects($this->any())
            ->method('getSession')
            ->willReturn($session);

        $this->extension = new RequestDataStorageExtension(
            $requestStack,
            $this->storage,
            PropertyAccess::createPropertyAccessor(),
            $this->doctrineHelper,
            $this->aclHelper,
            $this->logger,
            $this->productAvailabilityProvider,
            $translator,
            $this->twig
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getTargetEntity(): object
    {
        return $this->entity;
    }

    public function testBuildForm(): void
    {
        $sku = 'TEST';
        $qty = 3;
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => $qty,
                ]
            ]
        ];

        $productUnit = $this->getProductUnit('item');
        $product = $this->getProduct($sku, $productUnit);

        $this->productAvailabilityProvider->expects($this->once())
            ->method('isProductAllowedForRFP')
            ->with($product)
            ->willReturn(true);

        $this->expectsEntityMetadata();
        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);
        $this->expectsGetProductFromEntityRepository($product);

        $this->extension->buildForm($this->getFormBuilder(), []);

        $this->assertCount(1, $this->entity->getRequestProducts());
        /** @var RequestProduct $requestProduct */
        $requestProduct = $this->entity->getRequestProducts()->first();

        $this->assertEquals($product, $requestProduct->getProduct());
        $this->assertEquals($product->getSku(), $requestProduct->getProductSku());

        $this->assertCount(1, $requestProduct->getRequestProductItems());
        /** @var RequestProductItem $requestProductItem */
        $requestProductItem = $requestProduct->getRequestProductItems()->first();

        $this->assertEquals($productUnit, $requestProductItem->getProductUnit());
        $this->assertEquals($productUnit->getCode(), $requestProductItem->getProductUnitCode());
        $this->assertEquals($qty, $requestProductItem->getQuantity());
    }

    public function testBuildFormNotAllowedForRFPProduct(): void
    {
        $sku = 'TEST';
        $qty = 3;
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => $qty,
                ]
            ]
        ];

        $productUnit = $this->getProductUnit('item');
        $product = $this->getProduct($sku, $productUnit);

        $this->productAvailabilityProvider->expects($this->once())
            ->method('isProductAllowedForRFP')
            ->with($product)
            ->willReturn(false);

        $this->expectsEntityMetadata();
        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);
        $this->expectsGetProductFromEntityRepository($product);
        $this->expectsWarningFlashMessage([$product]);

        $this->extension->buildForm($this->getFormBuilder(), []);

        $this->assertEmpty($this->entity->getRequestProducts());
    }

    private function expectsWarningFlashMessage(array $canNotBeAddedToRFQProducts): void
    {
        $warningRenderedMessage = 'warning message';
        $this->twig->expects(self::once())
            ->method('render')
            ->with(
                '@OroRFP/Form/FlashBag/warning.html.twig',
                [
                    'message' => 'oro.frontend.rfp.data_storage.cannot_be_added_to_rfq_translated',
                    'products' => $canNotBeAddedToRFQProducts
                ]
            )
            ->willReturn($warningRenderedMessage);

        $this->flashBag->expects(self::once())
            ->method('add')
            ->with('warning', $warningRenderedMessage);
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([RequestType::class], RequestDataStorageExtension::getExtendedTypes());
    }
}
