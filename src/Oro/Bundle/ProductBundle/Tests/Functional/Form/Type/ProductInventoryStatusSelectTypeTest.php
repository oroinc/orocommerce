<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Form\Type;

use Oro\Bundle\ProductBundle\Form\Type\ProductInventoryStatusSelectType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class ProductInventoryStatusSelectTypeTest extends WebTestCase
{
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->formFactory = $this->getContainer()->get('form.factory');
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $submitData, bool $isValid)
    {
        // submit form
        $form = $this->formFactory->create(ProductInventoryStatusSelectType::class, []);
        $form->submit($submitData);
        $this->assertEquals($isValid, $form->isValid());
        $this->assertTrue($form->isSynchronized());
        if ($isValid) {
            $this->assertEquals($submitData, $form->getData());
        }
    }

    public function submitDataProvider(): array
    {
        return [
            'empty data' => [
                'submitData' => [],
                'isValid' => true,
            ],
            'invalid data' => [
                'submitData' => ['test'],
                'isValid' => false,
            ],
            'valid data' => [
                'submitData' => [
                    'prod_inventory_status.in_stock',
                    'prod_inventory_status.out_of_stock',
                    'prod_inventory_status.discontinued',
                ],
                'isValid' => true,
            ],
        ];
    }
}
