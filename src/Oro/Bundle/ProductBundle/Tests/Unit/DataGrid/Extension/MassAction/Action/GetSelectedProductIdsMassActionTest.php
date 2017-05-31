<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\Extension\Action\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\ProductBundle\DataGrid\Extension\MassAction\Action\GetSelectedProductIdsMassAction;

class GetSelectedProductIdsMassActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GetSelectedProductIdsMassAction
     */
    private $action;

    protected function setUp()
    {
        $this->action = new GetSelectedProductIdsMassAction();
    }

    /**
     * @param array $source
     * @param array $expected
     * @dataProvider setOptionsDataProvider
     */
    public function testSetOptions(array $source, array $expected)
    {
        $this->action->setOptions(ActionConfiguration::create($source));

        $actual = $this->action->getOptions();
        foreach ($expected as $name => $value) {
            $this->assertEquals($value, $actual->offsetGet($name));
        }
    }

    /**
     * @return array
     */
    public function setOptionsDataProvider()
    {
        return [
            'with custom options' => [
                'source' => [
                    'handler' => 'test.handler',
                    'frontend_type' => 'test_type',
                    'frontend_handle' => 'test_frontend_handle',
                    'data_identifier' => 'some.id',
                ],
                'expected' => [
                    'handler' => 'test.handler',
                    'frontend_type' => 'test_type',
                    'frontend_handle' => 'test_frontend_handle',
                    'confirmation' => false,
                    'data_identifier' => 'some.id',
                ],
            ],
            'just default options' => [
                'source' => [
                    'data_identifier' => 'product.id',
                ],
                'expected' => [
                    'handler'
                        => 'oro_product.datagrid.extension.mass_action.get_selected_product_ids_mass_action_handler',
                    'frontend_type' => 'get-selected-product-ids-mass',
                    'frontend_handle' => 'ajax',
                    'confirmation' => false,
                    'data_identifier' => 'product.id',
                ],
            ],
        ];
    }
}
