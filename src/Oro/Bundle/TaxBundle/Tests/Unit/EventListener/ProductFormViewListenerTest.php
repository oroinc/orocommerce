<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\EventListener\ProductFormViewListener;
use Oro\Bundle\ProductBundle\Entity\Product;
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
            'Oro\Bundle\TaxBundle\Entity\ProductTaxCode',
            'Oro\Bundle\ProductBundle\Entity\Product'
        );
    }

    public function testOnEdit()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $htmlTemplate = 'tax_code_update_template';
        $env->expects($this->once())
            ->method('render')
            ->with('OroTaxBundle:Product:tax_code_update.html.twig', ['form' => new FormView()])
            ->willReturn($htmlTemplate);

        $data = [
            ScrollData::DATA_BLOCKS => [
                'firstBlock' => [
                    ScrollData::TITLE => 'first block',
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                                'first subblock data',
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

        $scrollData = new ScrollData($data);
        $event = new BeforeListRenderEvent($env, $scrollData, new FormView());

        $this->getListener()->onEdit($event);
        $expectedData = $data;
        $expectedData[ScrollData::DATA_BLOCKS]['firstBlock'][ScrollData::SUB_BLOCKS][0][ScrollData::DATA][] =
            $htmlTemplate;

        $this->assertEquals($expectedData, $scrollData->getData());
    }

    public function testOnProductView()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository $repository */
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneByProduct'])
            ->getMock();
        $taxCode = new ProductTaxCode();
        $repository
            ->expects($this->once())
            ->method('findOneByProduct')
            ->willReturn($taxCode);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn(new Product());
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->willReturn($repository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $env->expects($this->once())
            ->method('render')
            ->with('OroTaxBundle:Product:tax_code_view.html.twig', ['entity' => $taxCode])
            ->willReturn('');

        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->getListener()->onView($event);
    }
}
