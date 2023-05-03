<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Form\DataTransformer\ProductVariantLinksDataTransformer;
use Oro\Bundle\ProductBundle\Form\Type\ProductVariantLinksType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ProductVariantLinksTypeTest extends FormIntegrationTestCase
{
    /** @var ProductVariantLinksType */
    private $formType;

    /** @var array */
    private $defaultData = [
        'appendVariants' => [],
        'removeVariants' => []
    ];

    /** @var ProductVariantLinksDataTransformer|\PHPUnit\Framework\MockObject\MockObject */
    private $transformer;

    /** @var array */
    private $products = [];

    protected function setUp(): void
    {
        $this->transformer = $this->createMock(ProductVariantLinksDataTransformer::class);
        $this->formType = new ProductVariantLinksType($this->transformer);
        parent::setUp();
    }

    public function testSubmitNoChanges()
    {
        $submittedData = $this->prepareVariantsData();
        $expectedData = $this->prepareExpectedVariantLinks();

        $this->transformer->expects(self::once())
            ->method('transform')
            ->with($this->prepareVariantsData())
            ->willReturn(null);
        $this->transformer->expects(self::once())
            ->method('reverseTransform')
            ->with($this->prepareVariantsData())
            ->willReturn($expectedData);

        $form = $this->factory->create(ProductVariantLinksType::class, $this->defaultData);
        $this->assertEquals($this->defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    public function testSubmitAddVariants()
    {
        $submittedData = $this->prepareVariantsData([1, 2]);
        $expectedData = $this->prepareExpectedVariantLinks([$this->products[1], $this->products[2]]);

        $this->transformer->expects(self::once())
            ->method('transform')
            ->with($this->prepareVariantsData())
            ->willReturn(null);
        $this->transformer->expects(self::once())
            ->method('reverseTransform')
            ->with($this->prepareVariantsData([$this->products[1], $this->products[2]]))
            ->willReturn($expectedData);

        $form = $this->factory->create(ProductVariantLinksType::class, $this->defaultData);
        $this->assertEquals($this->defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    public function testSubmitRemoveVariants()
    {
        $submittedData = $this->prepareVariantsData([], [3, 4]);
        $expectedData = $this->prepareExpectedVariantLinks();

        $this->transformer->expects(self::once())
            ->method('transform')
            ->with($this->prepareVariantsData())
            ->willReturn(null);
        $this->transformer->expects(self::once())
            ->method('reverseTransform')
            ->with($this->prepareVariantsData([], [$this->products[3], $this->products[4]]))
            ->willReturn($expectedData);

        $form = $this->factory->create(ProductVariantLinksType::class, $this->defaultData);
        $this->assertEquals($this->defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    public function testSubmitAddAndRemoveVariants()
    {
        $submittedData = $this->prepareVariantsData([1, 2], [3, 4]);
        $expectedData = $this->prepareExpectedVariantLinks([$this->products[1], $this->products[2]]);

        $this->transformer->expects(self::once())
            ->method('transform')
            ->with($this->prepareVariantsData())
            ->willReturn(null);
        $this->transformer->expects(self::once())
            ->method('reverseTransform')
            ->with($this->prepareVariantsData(
                [$this->products[1], $this->products[2]],
                [$this->products[3], $this->products[4]]
            ))
            ->willReturn($expectedData);

        $form = $this->factory->create(ProductVariantLinksType::class, $this->defaultData);
        $this->assertEquals($this->defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $this->initProducts();

        return [
            new PreloadedExtension([
                $this->formType,
                EntityIdentifierType::class => new EntityTypeStub($this->products)
            ], [])
        ];
    }

    private function prepareVariantsData(array $appendVariants = [], array $removeVariants = []): array
    {
        return [
            'appendVariants' => $appendVariants,
            'removeVariants' => $removeVariants
        ];
    }

    private function initProducts(): void
    {
        if (count($this->products) === 0) {
            $this->products[1] = (new Product())->setSku('sku1');
            $this->products[2] = (new Product())->setSku('sku2');
            $this->products[3] = (new Product())->setSku('sku3');
            $this->products[4] = (new Product())->setSku('sku4');
        }
    }

    private function prepareExpectedVariantLinks(array $products = []): ArrayCollection
    {
        $expectedVariantLinks = new ArrayCollection([]);
        foreach ($products as $product) {
            $expectedVariantLinks->add(new ProductVariantLink(null, $product));
        }

        return $expectedVariantLinks;
    }
}
