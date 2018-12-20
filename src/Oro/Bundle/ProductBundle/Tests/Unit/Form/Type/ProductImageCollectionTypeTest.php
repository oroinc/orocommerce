<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Form\Type\ProductImageCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductImageType;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ImageTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ProductImageCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductImageCollectionType
     */
    protected $formType;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ImageTypeProvider
     */
    protected $imageTypeProvider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->imageTypeProvider = $this
            ->getMockBuilder('Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->imageTypeProvider->expects($this->any())
            ->method('getImageTypes')
            ->willReturn([
                new ThemeImageType('main', 'Main', [], 1),
                new ThemeImageType('listing', 'Listing', [], 2)
            ]);

        $this->formType = new ProductImageCollectionType($this->imageTypeProvider);

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    ProductImageCollectionType::class => $this->formType,
                    CollectionType::class => new CollectionType(),
                    ProductImageType::class => new ProductImageType(),
                    ImageType::class => new ImageTypeStub()
                ],
                []
            )
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->formType);
    }

    /**
     * @param array|null $defaultData
     * @param array|null $submittedData
     * @param array|null $expectedData
     * @param array $options
     * @dataProvider submitDataProvider
     */
    public function testSubmit($defaultData, $submittedData, $expectedData, array $options)
    {
        $form = $this->factory->create(ProductImageCollectionType::class, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $formData = $form->getData();

        $this->syncUpdatedAt($expectedData, $formData);
        $this->assertEquals($expectedData, $formData);
    }

    public function submitDataProvider()
    {
        $file = new File(__DIR__ . '/files/black.jpg');

        $productImage = new ProductImage();
        $productImage->addType('main');

        $defaultProductImage = new StubProductImage();
        $defaultProductImage->addType('test');

        return [
            'without submitted data' => [
                'defaultData' => null,
                'submittedData' => null,
                'expectedData' => [],
                'options' => []
            ],
            'without default data' => [
                'defaultData' => null,
                'submittedData' => [
                    [
                        'image' => $file,
                        'main' => 1
                    ]
                ],
                'expectedData' => [
                    $productImage
                ],
                'options' => []
            ],
            'with default data' => [
                'defaultData' => [
                    $defaultProductImage
                ],
                'submittedData' => [
                    [
                        'image' => $file,
                        'test' => 1
                    ],
                    [
                        'image' => $file,
                        'main' => 1
                    ]
                ],
                'expectedData' => [
                    $defaultProductImage,
                    $productImage
                ],
                'options' => []
            ]
        ];
    }

    /**
     * @param array $expectedData
     * @param array $formData
     */
    protected function syncUpdatedAt($expectedData, $formData)
    {
        $now = new \DateTime();
        /** @var ProductImage $productImage */
        foreach ($expectedData as $productImage) {
            $productImage->setUpdatedAt($now);
        }
        /** @var ProductImage $productImage */
        foreach ($formData as $productImage) {
            $productImage->setUpdatedAt($now);
        }
    }
}
