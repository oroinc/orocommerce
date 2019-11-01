<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\EventListener\ProductFormViewListener;
use Oro\Bundle\TaxBundle\Tests\Unit\Entity\ProductStub;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\Form\FormView;

class ProductFormViewListenerTest extends AbstractFormViewListenerTest
{
    /**
     * @var ProductFormViewListener
     */
    protected $listener;

    /**
     * @return ProductFormViewListener
     */
    public function getListener()
    {
        return new ProductFormViewListener(
            $this->doctrineHelper,
            $this->requestStack,
            ProductTaxCode::class,
            Product::class
        );
    }

    public function testOnEdit()
    {
        $htmlTemplate = 'tax_code_update_template';
        $formView = new FormView();

        $this->env->expects($this->once())
            ->method('render')
            ->with('OroTaxBundle:Product:tax_code_update.html.twig', ['form' => $formView])
            ->willReturn($htmlTemplate);

        $scrollData = new ScrollData([
            ScrollData::DATA_BLOCKS => [
                'general' => [
                    ScrollData::TITLE => 'first block',
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                                'first subblock data',
                            ]
                        ],
                        1 => [
                            ScrollData::DATA => [
                                'second subblock data',
                            ]
                        ],
                    ]
                ],
                0 => [
                    ScrollData::TITLE => 'first block',
                    ScrollData::SUB_BLOCKS => []
                ]
            ]
        ]);

        $event = new BeforeListRenderEvent($this->env, $scrollData, new \stdClass(), $formView);

        $this->getListener()->onEdit($event);

        $expectedData = [
            ScrollData::DATA_BLOCKS => [
                'general' => [
                    ScrollData::TITLE => 'first block',
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                                'first subblock data',
                            ]
                        ],
                        1 => [
                            ScrollData::DATA => [
                                'second subblock data',
                                $htmlTemplate,
                            ]
                        ],
                    ]
                ],
                0 => [
                    ScrollData::TITLE => 'first block',
                    ScrollData::SUB_BLOCKS => []
                ]
            ]
        ];

        $this->assertEquals($expectedData, $scrollData->getData());
    }

    public function testOnProductView()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $taxCode = new ProductTaxCode();

        $product = new ProductStub();
        $product->setTaxCode($taxCode);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->with(Product::class, 1)
            ->willReturn($product);

        $this->env->expects($this->once())
            ->method('render')
            ->with('OroTaxBundle:Product:tax_code_view.html.twig', ['entity' => $taxCode])
            ->willReturn('rendered');

        $scrollData = new ScrollData([
            ScrollData::DATA_BLOCKS => [
                'general' => [
                    ScrollData::TITLE => 'first block',
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                                'first subblock data',
                            ]
                        ],
                        1 => [
                            ScrollData::DATA => [
                                'second subblock data',
                            ]
                        ],
                    ],
                ],
            ],
        ]);

        $event = new BeforeListRenderEvent(
            $this->env,
            $scrollData,
            $taxCode
        );

        $this->getListener()->onView($event);

        $expectedData = [
            ScrollData::DATA_BLOCKS => [
                'general' => [
                    ScrollData::TITLE => 'first block',
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                                'first subblock data',
                            ]
                        ],
                        1 => [
                            ScrollData::DATA => [
                                'second subblock data',
                                'rendered',
                            ]
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedData, $scrollData->getData());
    }
}
