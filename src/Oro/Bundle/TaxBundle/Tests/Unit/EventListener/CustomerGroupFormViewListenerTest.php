<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\EventListener\CustomerGroupFormViewListener;
use Oro\Bundle\TaxBundle\Tests\Unit\Entity\CustomerGroupStub;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\Form\FormView;

class CustomerGroupFormViewListenerTest extends AbstractFormViewListenerTest
{
    /**
     * @var CustomerGroupFormViewListener
     */
    protected $listener;

    /**
     * @return CustomerGroupFormViewListener
     */
    public function getListener()
    {
        return new CustomerGroupFormViewListener(
            $this->doctrineHelper,
            $this->requestStack,
            CustomerTaxCode::class,
            CustomerGroup::class
        );
    }

    public function testOnEdit()
    {
        $formView = new FormView();

        $this->env->expects($this->once())
            ->method('render')
            ->with('OroTaxBundle:CustomerGroup:tax_code_update.html.twig', ['form' => $formView])
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

    public function testOnCustomerGroupView()
    {
        $this->request->expects($this->any())->method('get')->with('id')->willReturn(1);

        $taxCode = new CustomerTaxCode();

        $customerGroup = new CustomerGroupStub();
        $customerGroup->setTaxCode($taxCode);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->with(CustomerGroup::class, 1)
            ->willReturn($customerGroup);

        $this->env->expects($this->once())
            ->method('render')
            ->with('OroTaxBundle:CustomerGroup:tax_code_view.html.twig', ['entity' => $taxCode])
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
            $taxCode
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
