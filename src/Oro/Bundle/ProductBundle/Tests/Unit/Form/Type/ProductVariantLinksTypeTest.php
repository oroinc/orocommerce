<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Form\DataTransformer\ProductVariantLinksDataTransformer;
use Oro\Bundle\ProductBundle\Form\Type\ProductVariantLinksType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ProductVariantLinksTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductVariantLinksType
     */
    protected $formType;

    /**
     * @var array
     */
    protected $defaultData = [
        'appendVariants' => [],
        'removeVariants' => []
    ];

    /**
     * @var ProductVariantLinksDataTransformer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $transformer;

    /**
     * @var array
     */
    protected $products = [];

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->transformer = $this->createMock(
            'Oro\Bundle\ProductBundle\Form\DataTransformer\ProductVariantLinksDataTransformer'
        );
        $this->formType = new ProductVariantLinksType($this->transformer);
        parent::setUp();
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit($submittedData, $expectedData, $transformerCalls)
    {
        $this->addTransformerCalls($transformerCalls);

        $form = $this->factory->create(ProductVariantLinksType::class, $this->defaultData);

        $this->assertEquals($this->defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $this->initProducts();

        $expectedDataNoChanges = $this->prepareExpectedVariantLinks();
        $expectedDataAddVariants = $this->prepareExpectedVariantLinks(
            [$this->products[1], $this->products[2]]
        );
        $expectedDataRemoveVariants = $this->prepareExpectedVariantLinks([]);
        $expectedDataAddAndRemoveVariants = $this->prepareExpectedVariantLinks(
            [$this->products[1], $this->products[2]]
        );

        return [
            'no changes' => [
                'submittedData' => $this->prepareVariantsData(),
                'expectedData' => $expectedDataNoChanges,
                'transformerCalls' => [
                    ['transform', [$this->prepareVariantsData()], null],
                    [
                        'reverseTransform',
                        [$this->prepareVariantsData()],
                        $expectedDataNoChanges
                    ]
                ]
            ],
            'add variants' => [
                'submittedData' => $this->prepareVariantsData([1, 2]),
                'expectedData' => $expectedDataAddVariants,
                'transformerCalls' => [
                    ['transform', [$this->prepareVariantsData()], null],
                    [
                        'reverseTransform',
                        [$this->prepareVariantsData([$this->products[1], $this->products[2]])],
                        $expectedDataAddVariants
                    ]
                ]
            ],
            'remove variants' => [
                'submittedData' => $this->prepareVariantsData([], [3, 4]),
                'expectedData' => $expectedDataRemoveVariants,
                'transformerCalls' => [
                    ['transform', [$this->prepareVariantsData()], null],
                    [
                        'reverseTransform',
                        [$this->prepareVariantsData([], [$this->products[3], $this->products[4]])],
                        $expectedDataRemoveVariants
                    ]
                ]
            ],
            'add and remove variants' => [
                'submittedData' => $this->prepareVariantsData([1, 2], [3, 4]),
                'expectedData' => $expectedDataAddAndRemoveVariants,
                'transformerCalls' => [
                    ['transform', [$this->prepareVariantsData()], null],
                    [
                        'reverseTransform',
                        [$this->prepareVariantsData(
                            [$this->products[1], $this->products[2]],
                            [$this->products[3], $this->products[4]]
                        )],
                        $expectedDataAddAndRemoveVariants
                    ]
                ]
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $this->initProducts();

        return [
            new PreloadedExtension([
                $this->formType,
                EntityIdentifierType::class => new EntityType($this->products)
            ], [])
        ];
    }

    private function addTransformerCalls(array $transformerCalls)
    {
        $index = 0;

        foreach ($transformerCalls as $expectedCall) {
            list($method, $arguments, $result) = $expectedCall;

            if (is_callable($result)) {
                $result = call_user_func($result);
            }

            $methodExpectation = $this->transformer->expects($this->at($index++))->method($method);
            $methodExpectation = call_user_func_array([$methodExpectation, 'with'], $arguments);
            $methodExpectation->will($this->returnValue($result));
        }
    }

    /**
     * @param array $appendVariants
     * @param array $removeVariants
     * @return array
     */
    private function prepareVariantsData(array $appendVariants = [], array $removeVariants = [])
    {
        return [
            'appendVariants' => $appendVariants,
            'removeVariants' => $removeVariants
        ];
    }

    protected function initProducts()
    {
        if (count($this->products) === 0) {
            $this->products[1] = (new Product())->setSku('sku1');
            $this->products[2] = (new Product())->setSku('sku2');
            $this->products[3] = (new Product())->setSku('sku3');
            $this->products[4] = (new Product())->setSku('sku4');
        }
    }

    /**
     * @param array $products
     * @return ArrayCollection
     */
    protected function prepareExpectedVariantLinks(array $products = [])
    {
        $expectedVariantLinks = new ArrayCollection([]);

        foreach ($products as $product) {
            $expectedVariantLinks->add(new ProductVariantLink(null, $product));
        }

        return $expectedVariantLinks;
    }
}
