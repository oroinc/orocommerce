<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SEOBundle\EventListener\ProductFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Twig\Environment;

class ProductFormViewListenerTest extends BaseFormViewListenerTestCase
{
    /** @var ProductFormViewListener */
    protected $listener;

    protected function setUp(): void
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
     * @return \PHPUnit\Framework\MockObject\MockObject|Environment
     */
    protected function getEnvironmentForView($entityObject, $labelPrefix)
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|Environment $env */
        $env = $this->getMockBuilder(Environment::class)
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
