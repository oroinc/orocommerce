<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SEOBundle\EventListener\ProductFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

class ProductFormViewListenerTest extends BaseFormViewListenerTestCase
{
    /** @var ProductFormViewListener */
    protected $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->listener = new ProductFormViewListener($this->translator);
    }

    protected function terDown()
    {
        unset($this->listener);

        parent::tearDown();
    }

    public function testOnProductView()
    {
        $product = new Product();

        $env = $this->getEnvironmentForView($product, $this->listener->getMetaFieldLabelPrefix());
        $scrollData = new ScrollData();

        $event = new BeforeListRenderEvent($env, $scrollData, $product);

        $this->listener->onProductView($event);
    }

    public function testOnProductEdit()
    {
        $product = new Product();

        $env = $this->getEnvironmentForEdit();
        $scrollData = new ScrollData();

        $event = new BeforeListRenderEvent($env, $scrollData, $product);

        $this->listener->onProductEdit($event);
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

        $env->expects($this->exactly(4))
            ->method('render')
            ->willReturnMap([
                [
                    'OroSEOBundle:SEO:title_view.html.twig',
                    [
                        'entity' => $entityObject,
                        'labelPrefix' => $labelPrefix,
                    ],
                    '',
                ],                [
                    'OroSEOBundle:SEO:description_view.html.twig',
                    [
                        'entity' => $entityObject,
                        'labelPrefix' => $labelPrefix,
                    ],
                    '',
                ],
                [
                    'OroSEOBundle:SEO:keywords_view.html.twig',
                    [
                        'entity' => $entityObject,
                        'labelPrefix' => $labelPrefix,
                    ],
                    '',
                ],
                [
                    'OroRedirectBundle::entitySlugs.html.twig',
                    [
                        'entitySlugs' => $entityObject->getSlugs(),
                    ],
                    '',
                ],
            ]);

        return $env;
    }
}
