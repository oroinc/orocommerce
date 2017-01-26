<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\CustomerBundle\EventListener\Datagrid\CustomerUserDatagridListener;

class CustomerUserDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CustomerUserDatagridListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->listener = new CustomerUserDatagridListener();
    }

    /**
     * @param ParameterBag $parameters
     * @param DatagridConfiguration $expectedConfig
     *
     * @dataProvider dataProvider
     */
    public function testCustomerLimitations(ParameterBag $parameters, DatagridConfiguration $expectedConfig)
    {
        $event = new PreBuild($this->getConfig(), $parameters);

        $this->listener->onBuildBefore($event);

        $this->assertEquals($expectedConfig->toArray(), $event->getConfig()->toArray());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProvider()
    {
        return [
            'hasRole user condition only' => [
                $this->getParameters(),
                $this->getConfig(
                    [
                        'source' => [
                            'query' => [
                                'select' => [
                                    '(CASE WHEN user.id IN (:data_in) AND user.id NOT IN (:data_not_in) ' .
                                    'THEN true ELSE false END) as hasRole',
                                ],
                            ],
                        ],
                    ]
                ),
            ],
            'hasRole role condition' => [
                $this->getParameters(['role' => 1]),
                $this->getConfig(
                    [
                        'source' => [
                            'query' => [
                                'select' => [
                                    '(CASE WHEN (:role MEMBER OF user.roles OR user.id IN (:data_in)) AND user.id NOT' .
                                    ' IN (:data_not_in) THEN true ELSE false END) as hasRole',
                                ],
                            ],
                            'bind_parameters' => ['role'],
                        ],
                    ]
                ),
            ],
            'invalid additional parameters' => [
                $this->getParameters([ParameterBag::ADDITIONAL_PARAMETERS => true]),
                $this->getConfig(
                    [
                        'source' => [
                            'query' => [
                                'select' => [
                                    '(CASE WHEN user.id IN (:data_in) AND user.id NOT IN (:data_not_in) ' .
                                    'THEN true ELSE false END) as hasRole',
                                ],
                            ],
                        ],
                    ]
                ),
            ],
            'dont limit customer without customer id' => [
                $this->getParameters([ParameterBag::ADDITIONAL_PARAMETERS => []]),
                $this->getConfig(
                    [
                        'source' => [
                            'query' => [
                                'select' => [
                                    '(CASE WHEN user.id IN (:data_in) AND user.id NOT IN (:data_not_in) ' .
                                    'THEN true ELSE false END) as hasRole',
                                ],
                            ],
                        ],
                    ]
                ),
            ],
            'limit customer with customer id' => [
                $this->getParameters(
                    [ParameterBag::ADDITIONAL_PARAMETERS => [], 'customer' => 1]
                ),
                $this->getConfig(
                    [
                        'source' => [
                            'query' => [
                                'select' => [
                                    '(CASE WHEN user.id IN (:data_in) AND user.id NOT IN (:data_not_in) ' .
                                    'THEN true ELSE false END) as hasRole',
                                ],
                                'where' => ['or' => ['user.customer = :customer']],
                            ],
                            'bind_parameters' => ['customer'],
                        ],
                    ]
                ),
            ],
            'dont limit customer if change triggered' => [
                $this->getParameters(
                    [
                        ParameterBag::ADDITIONAL_PARAMETERS => ['changeCustomerAction' => true,],
                        'customer' => 1,
                    ]
                ),
                $this->getConfig(
                    [
                        'source' => [
                            'query' => [
                                'select' => [
                                    '(CASE WHEN user.id IN (:data_in) AND user.id NOT IN (:data_not_in) ' .
                                    'THEN true ELSE false END) as hasRole',
                                ],
                            ],
                        ],
                    ]
                ),
            ],
            'limit new customer if change triggered' => [
                $this->getParameters(
                    [
                        ParameterBag::ADDITIONAL_PARAMETERS => [
                            'changeCustomerAction' => true,
                            'newCustomer' => 1,
                        ],
                    ]
                ),
                $this->getConfig(
                    [
                        'source' => [
                            'query' => [
                                'select' => [
                                    '(CASE WHEN user.id IN (:data_in) AND user.id NOT IN (:data_not_in) ' .
                                    'THEN true ELSE false END) as hasRole',
                                ],
                                'where' => ['or' => ['user.customer = :newCustomer']],
                            ],
                            'bind_parameters' => [['name' => 'newCustomer', 'path' => '_parameters.newCustomer']],
                        ],
                    ]
                ),
            ],
        ];
    }

    /**
     * @param array $params
     * @return DatagridConfiguration
     */
    protected function getConfig(array $params = [])
    {
        return DatagridConfiguration::create($params);
    }

    /**
     * @param array $params
     * @return ParameterBag
     */
    protected function getParameters(array $params = [])
    {
        return new ParameterBag($params);
    }
}
