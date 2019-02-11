<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\EventListener\CustomerFormViewListener;
use Oro\Bundle\TaxBundle\Tests\Unit\Entity\CustomerGroupStub;
use Oro\Bundle\TaxBundle\Tests\Unit\Entity\CustomerStub;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\Form\FormView;

class CustomerFormViewListenerTest extends AbstractFormViewListenerTest
{
    /**
     * @var CustomerFormViewListener
     */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    public function getListener()
    {
        return new CustomerFormViewListener(
            $this->doctrineHelper,
            $this->requestStack,
            CustomerTaxCode::class,
            Customer::class
        );
    }

    public function testOnEdit()
    {
        $formView = new FormView();

        $this->env->expects($this->once())
            ->method('render')
            ->with('OroTaxBundle:Customer:tax_code_update.html.twig', ['form' => $formView])
            ->willReturn('rendered');

        $scrollData = new ScrollData([
            ScrollData::DATA_BLOCKS => [
                [
                    ScrollData::SUB_BLOCKS => [
                        [
                            ScrollData::DATA => []
                        ]
                    ]
                ]
            ]
        ]);

        $event = new BeforeListRenderEvent(
            $this->env,
            $scrollData,
            new \stdClass(),
            $formView
        );

        $this->getListener()->onEdit($event);

        $expectedData = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                                0 => 'rendered',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedData, $scrollData->getData());
    }

    public function testOnCustomerView()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $taxCode = new CustomerTaxCode();

        $customer = new CustomerStub();
        $customer->setTaxCode($taxCode);
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->with(Customer::class, 1)
            ->willReturn($customer);

        $this->env->expects($this->once())
            ->method('render')
            ->with(
                'OroTaxBundle:Customer:tax_code_view.html.twig',
                [
                    'entity' => $taxCode,
                    'groupCustomerTaxCode' => null,
                ]
            )
            ->willReturn('rendered');

        $scrollData = new ScrollData([
            ScrollData::DATA_BLOCKS => [
                [
                    ScrollData::SUB_BLOCKS => [
                        [
                            ScrollData::DATA => []
                        ]
                    ]
                ]
            ]
        ]);

        $event = new BeforeListRenderEvent(
            $this->env,
            $scrollData,
            new \stdClass()
        );

        $this->getListener()->onView($event);

        $expectedData = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                                0 => 'rendered',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedData, $scrollData->getData());
    }

    public function testOnCustomerViewWithCustomerGroupTaxCode()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $customerTaxCode = new CustomerTaxCode();

        $customerGroup = new CustomerGroupStub();
        $customerGroup->setTaxCode($customerTaxCode);

        $customer = new CustomerStub();
        $customer->setGroup($customerGroup);
        // customer doesn't have tax code
        $customer->setTaxCode(null);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->with(Customer::class, 1)
            ->willReturn($customer);

        $this->env->expects($this->once())
            ->method('render')
            ->with(
                'OroTaxBundle:Customer:tax_code_view.html.twig',
                [
                    'entity' => null,
                    'groupCustomerTaxCode' => $customerTaxCode,
                ]
            )
            ->willReturn('rendered');

        $scrollData = new ScrollData([
            ScrollData::DATA_BLOCKS => [
                [
                    ScrollData::SUB_BLOCKS => [
                        [
                            ScrollData::DATA => []
                        ]
                    ]
                ]
            ]
        ]);

        $event = new BeforeListRenderEvent(
            $this->env,
            $scrollData,
            $customerTaxCode
        );

        $this->getListener()->onView($event);

        $expectedData = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                                0 => 'rendered',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedData, $scrollData->getData());
    }

    public function testOnCustomerViewAllEmpty()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $customerGroup = new CustomerGroupStub();

        $customer = new CustomerStub();
        $customer->setGroup($customerGroup);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->with(Customer::class, 1)
            ->willReturn($customer);

        $this->env->expects($this->once())
            ->method('render')
            ->with(
                'OroTaxBundle:Customer:tax_code_view.html.twig',
                [
                    'entity' => null,
                    'groupCustomerTaxCode' => null,
                ]
            )
            ->willReturn('rendered');

        $scrollData = new ScrollData([
            ScrollData::DATA_BLOCKS => [
                [
                    ScrollData::SUB_BLOCKS => [
                        [
                            ScrollData::DATA => []
                        ]
                    ]
                ]
            ]
        ]);

        $event = new BeforeListRenderEvent(
            $this->env,
            $scrollData,
            new \stdClass()
        );

        $this->getListener()->onView($event);

        $expectedData = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                                0 => 'rendered',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedData, $scrollData->getData());
    }
}
