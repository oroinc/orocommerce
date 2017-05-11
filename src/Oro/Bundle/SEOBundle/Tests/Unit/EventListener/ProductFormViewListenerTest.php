<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\EventListener\Traits\FormViewListenerWrongProductTestTrait;
use Oro\Bundle\SEOBundle\EventListener\ProductFormViewListener;
use Oro\Bundle\UIBundle\View\ScrollData;

class ProductFormViewListenerTest extends BaseFormViewListenerTestCase
{
    use FormViewListenerWrongProductTestTrait;

    /** @var ProductFormViewListener */
    protected $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->listener = new ProductFormViewListener($this->requestStack, $this->translator, $this->doctrineHelper);
    }

    protected function terDown()
    {
        unset($this->listener);

        parent::tearDown();
    }

    public function testOnProductView()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $product = new Product();
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn($product);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getEnvironmentForView($product, $this->listener->getMetaFieldLabelPrefix());
        $event = $this->getEventForView($env);

        $this->listener->onProductView($event);
    }

    public function testOnProductEdit()
    {
        $env = $this->getEnvironmentForEdit();
        $event = $this->getEventForEdit($env);

        $this->listener->onProductEdit($event);
    }

    /**
     * {@inheritdoc}
     */
    protected function getScrollData()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ScrollData $scrollData */
        $scrollData = $this->createMock(ScrollData::class);
        $scrollData->expects($this->any())
            ->method('addSubBlockData');

        return $scrollData;
    }

    /**
     * @param object $entityObject
     * @param string $labelPrefix
     * @return \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment
     */
    protected function getEnvironmentForView($entityObject, $labelPrefix)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder(\Twig_Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $env->expects($this->exactly(3))
            ->method('render')
            ->willReturnMap([
                [
                    'OroSEOBundle:SEO:description_view.html.twig',
                    [
                        'entity' => $entityObject,
                        'labelPrefix' => $labelPrefix
                    ],
                    ''
                ],
                [
                    'OroSEOBundle:SEO:keywords_view.html.twig',
                    [
                        'entity' => $entityObject,
                        'labelPrefix' => $labelPrefix
                    ],
                    ''
                ],
                [
                    'OroRedirectBundle::entitySlugs.html.twig',
                    [
                        'entitySlugs' => $entityObject->getSlugs(),
                    ],
                    ''
                ]
            ]);

        return $env;
    }
}
