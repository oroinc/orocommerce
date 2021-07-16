<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\ProductBundle\Form\Type\ProductImageTypesType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ProductImageTypesTypeTest extends FormIntegrationTestCase
{
    /**
     * @param array|null $defaultData
     * @param array|null $submittedData
     * @param array $expectedTypes
     * @param array $options
     * @dataProvider submitDataProvider
     */
    public function testSubmit($defaultData, $submittedData, $expectedTypes, array $options): void
    {
        $form = $this->factory->create(ProductImageTypesType::class, $defaultData, $options);
        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedTypes, $form->getData());
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
                    'main',
                    'listing'
                ],
                'expectedTypes' => ['main', 'listing'],
                'options' => ['image_types' => $imageTypes]
            ],
            'with default data' => [
                'defaultData' => new ArrayCollection(['main', 'listing', 'additional']),
                'submittedData' => [
                    'main',
                ],
                'expectedTypes' => ['main'],
                'options' => ['image_types' => $imageTypes]
            ]
        ];
    }
}
