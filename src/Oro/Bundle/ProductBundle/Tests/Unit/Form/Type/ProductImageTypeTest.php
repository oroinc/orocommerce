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
     * {@inheritDoc}
     */
    protected function getExtensions(): array
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
     * @dataProvider submitDataProvider
     */
    public function testSubmit(?ProductImage $defaultData, ?array $submittedData, array $expectedTypes, array $options)
    {
        $form = $this->factory->create(ProductImageType::class, $defaultData, $options);
        $form->remove('image');

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        /** @var ProductImage $productImage */
        $productImage = $form->getData();
        $this->assertEquals($expectedTypes, array_keys($productImage->getTypes()->toArray()));
    }

    public function submitDataProvider(): array
    {
        $imageTypes = [
            new ThemeImageType('main', 'Main', []),
            new ThemeImageType('listing', 'Listing', []),
            new ThemeImageType('additional', 'Additional', []),
        ];

        return [
            'without default data' => [
                'defaultData' => null,
                'submittedData' => [
                    'main' => true
                ],
                'expectedTypes' => ['main'],
                'options' => ['image_types' => $imageTypes]
            ],
            'with default data' => [
                'defaultData' => new StubProductImage(),
                'submittedData' => [
                    'main' => true,
                    'listing' => true,
                    'additional' => true
                ],
                'expectedTypes' => ['main', 'listing', 'additional'],
                'options' => ['image_types' => $imageTypes]
            ]
        ];
    }
}
