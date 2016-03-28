<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Component\Layout\Extension\Theme\Model\ThemeImageType;

use OroB2B\Bundle\ProductBundle\Entity\ProductImage;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductImageType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductImageCollectionType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ImageTypeStub;

class ProductImageCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductImageCollectionType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ImageTypeProvider
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
                    'oro_collection' => new CollectionType(),
                    ProductImageType::NAME => new ProductImageType(),
                    ImageType::NAME => new ImageTypeStub()
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
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider()
    {
        $file = new File(__DIR__ . '/files/black.jpg');

        $productImage = new ProductImage();
        $productImage->setTypes(['main']);

        $defaultProductImage = new StubProductImage();
        $defaultProductImage->setTypes(['test']);

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

    public function testGetName()
    {
        $this->assertEquals(ProductImageCollectionType::NAME, $this->formType->getName());
    }
}
