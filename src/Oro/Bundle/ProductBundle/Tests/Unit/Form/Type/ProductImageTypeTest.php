<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Form\Type\ProductImageType;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ImageTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ProductImageTypeTest extends FormIntegrationTestCase
{
    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    ImageType::class => new ImageTypeStub()
                ],
                []
            )
        ];
    }

    /**
     * @param array|null $defaultData
     * @param array|null $submittedData
     * @param array $expectedTypes
     * @param array $options
     * @dataProvider submitDataProvider
     */
    public function testSubmit($defaultData, $submittedData, $expectedTypes, array $options)
    {
        $form = $this->factory->create(ProductImageType::class, $defaultData, $options);
        $form->remove('image');

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        /** @var ProductImage $productImage */
        $productImage  = $form->getData();
        $this->assertEquals($expectedTypes, array_keys($productImage->getTypes()->toArray()));
    }

    public function submitDataProvider()
    {
        $imageTypes = [
            new ThemeImageType('main', 'Main', []),
            new ThemeImageType('listing', 'Listing', []),
        ];

        $defaultProductImage = new StubProductImage();
        $defaultProductImage->addType('test');

        return [
            'without default data' => [
                'defaultData' => null,
                'submittedData' => [
                    'main' => 1
                ],
                'expectedTypes' => ['main'],
                'options' => ['image_types' => $imageTypes]
            ],
            'with default data' => [
                'defaultData' => $defaultProductImage,
                'submittedData' => [
                    'main' => 1,
                    'listing' => 1
                ],
                'expectedTypes' => ['main', 'listing'],
                'options' => ['image_types' => $imageTypes]
            ]
        ];
    }
}
