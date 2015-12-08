<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\RFPBundle\Form\Extension\OrderDataStorageExtension;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OrderDataStorageExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrderDataStorageExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RequestStack */
    protected $requestStack;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProductDataStorage */
    protected $productDataStorage;

    protected function setUp()
    {
        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $this->productDataStorage = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new OrderDataStorageExtension($this->requestStack, $this->productDataStorage);
    }

    public function testGetExtendedType()
    {
        $this->assertInternalType('string', $this->extension->getExtendedType());
        $this->assertEquals('orob2b_order_line_item', $this->extension->getExtendedType());
    }

    public function testConfigureOptionsWithoutRequest()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(null);

        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->never())->method('setNormalizer');
        $this->extension->configureOptions($resolver);
    }

    public function testConfigureOptionsWithRequestParam()
    {
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(null);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->never())->method('setNormalizer');
        $this->extension->configureOptions($resolver);
    }

    public function testConfigureOptions()
    {
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $this->productDataStorage->expects($this->once())->method('get')->willReturn(['offers' => [['quantity' => 1]]]);

        $resolver = new OptionsResolver();
        $resolver->setDefault('sections', []);
        $this->extension->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayHasKey('sections', $options);
        $this->assertArrayHasKey('offers', $options['sections']);
        $this->assertArrayHasKey('data', $options['sections']['offers']);
        $this->assertArrayHasKey('order', $options['sections']['offers']);
    }

    public function testConfigureOptionsWithoutData()
    {
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $resolver = new OptionsResolver();
        $resolver->setDefault('sections', []);
        $this->extension->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayHasKey('sections', $options);
        $this->assertArrayNotHasKey('offers', $options['sections']);
    }

    public function testFinishViewNotApplicable()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(null);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->never())->method('getParent');


        $this->extension->finishView(new FormView(), $form, []);
    }

    public function testFinishViewWithoutChild()
    {
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->never())->method('getParent');

        $this->extension->finishView(new FormView(), $form, []);
    }

    public function testFinishViewWithoutParent()
    {
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())->method('getData')->willReturn(new \stdClass());
        $form->expects($this->once())->method('getParent')->willReturn(null);

        $view = new FormView();
        $offersView = new FormView();
        $view->children['offers'] = $offersView;
        $this->extension->finishView($view, $form, []);

        $this->assertEmpty($offersView->vars['offers']);
    }

    public function testFinishViewWithoutFormData()
    {
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->never())->method('getParent');
        $form->expects($this->once())->method('getData')->willReturn(null);

        $view = new FormView();
        $offersView = new FormView();
        $view->children['offers'] = $offersView;
        $this->extension->finishView($view, $form, []);

        $this->assertEmpty($offersView->vars['offers']);
    }

    public function testFinishViewWrongParentData()
    {
        $entity = new \stdClass();

        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $parentForm */
        $parentForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())->method('getParent')->willReturn($parentForm);
        $form->expects($this->once())->method('getData')->willReturn($entity);
        $parentForm->expects($this->once())->method('getData')->willReturn(new \stdClass());

        $view = new FormView();
        $offersView = new FormView();
        $view->children['offers'] = $offersView;
        $this->extension->finishView($view, $form, []);

        $this->assertEmpty($offersView->vars['offers']);
    }

    public function testFinishViewMissingOffer()
    {
        $offer = ['quantity' => 1, 'unit' => 'kg'];
        $this->setOffers([[$offer]]);

        $entity = new \stdClass();
        $entity->prop = 'value';

        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $parentForm */
        $parentForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())->method('getParent')->willReturn($parentForm);
        $form->expects($this->once())->method('getData')->willReturn($entity);
        $parentForm->expects($this->once())->method('getData')->willReturn(
            new ArrayCollection([new \stdClass(), $entity])
        );

        $view = new FormView();
        $offersView = new FormView();
        $view->children['offers'] = $offersView;
        $this->extension->finishView($view, $form, []);

        $this->assertEmpty($offersView->vars['offers']);
    }

    public function testFinishViewWithWrongKey()
    {
        $entity = new \stdClass();

        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $parentForm */
        $parentForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())->method('getParent')->willReturn($parentForm);
        $form->expects($this->once())->method('getData')->willReturn($entity);
        $parentForm->expects($this->once())->method('getData')->willReturn(new ArrayCollection());

        $view = new FormView();
        $offersView = new FormView();
        $view->children['offers'] = $offersView;
        $this->extension->finishView($view, $form, []);

        $this->assertEmpty($offersView->vars['offers']);
    }

    public function testFinishViewSuccess()
    {
        $offer = ['quantity' => 1, 'unit' => 'kg'];
        $entity = new \stdClass();
        $this->setOffers([[$offer]]);

        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $parentForm */
        $parentForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())->method('getParent')->willReturn($parentForm);
        $form->expects($this->once())->method('getData')->willReturn($entity);
        $parentForm->expects($this->once())->method('getData')->willReturn(new ArrayCollection([$entity]));

        $view = new FormView();
        $offersView = new FormView();
        $view->children['offers'] = $offersView;
        $this->extension->finishView($view, $form, []);

        $this->assertSame($offer, $offersView->vars['offers'][0]);
    }

    public function testBuildFormNotApplicable()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(null);

        $this->productDataStorage->expects($this->never())->method($this->anything());

        $this->extension->buildForm($this->getBuilderMock(), []);
    }

    public function testBuildFormMissingKey()
    {
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $this->productDataStorage->expects($this->never())->method('get');

        $this->extension->buildForm($this->getBuilderMock(true), []);

        $this->assertEquals(false, $this->getOffers());
    }


    public function testBuildFormWrongType()
    {
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $this->productDataStorage->expects($this->never())->method('get');

        $this->extension->buildForm($this->getBuilderMock(true), []);

        $this->assertEquals(false, $this->getOffers());
    }


    public function testBuildForm()
    {
        $offer = ['quantity' => 1, 'unit' => 'kg'];

        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $this->productDataStorage->expects($this->once())->method('get')->willReturn(['offers' => [$offer]]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->once())->method('addEventListener')->with(
            $this->isType('string'),
            $this->logicalAnd(
                $this->isInstanceOf('\Closure'),
                $this->callback(
                    function (\Closure $closure) {
                        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
                        $form = $this->getMock('Symfony\Component\Form\FormInterface');
                        $parent = $this->getMock('Symfony\Component\Form\FormInterface');
                        $data = new \stdClass();

                        $form->expects($this->once())->method('getData')->willReturn($data);
                        $parent->expects($this->once())->method('getData')->willReturn(new ArrayCollection([$data]));
                        $form->expects($this->once())->method('getParent')->willReturn($parent);

                        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
                            ->disableOriginalConstructor()
                            ->getMock();

                        $event->expects($this->once())->method('getForm')->willReturn($form);
                        $closure($event);

                        return true;
                    }
                )
            )
        );

        $this->extension->buildForm($builder, []);

        $this->assertEquals([$offer], $this->getOffers());
    }

    /**
     * @param bool $expectsAddEventListener
     * @return \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface
     */
    protected function getBuilderMock($expectsAddEventListener = false)
    {
        /** @var  $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        if ($expectsAddEventListener) {
            $builder->expects($this->once())->method('addEventListener')->with(
                $this->isType('string'),
                $this->logicalAnd(
                    $this->isInstanceOf('\Closure'),
                    $this->callback(
                        function (\Closure $closure) {
                            /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
                            $form = $this->getMock('Symfony\Component\Form\FormInterface');
                            $form->expects($this->once())->method('add')->with(
                                $this->isType('string'),
                                $this->isType('string'),
                                $this->isType('array')
                            );

                            $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
                                ->disableOriginalConstructor()
                                ->getMock();

                            $event->expects($this->once())->method('getForm')->willReturn($form);
                            $closure($event);

                            return true;
                        }
                    )
                )
            );
        } else {
            $builder->expects($this->never())->method('addEventListener');
        }

        return $builder;
    }

    /**
     * @return array
     */
    protected function getOffers()
    {
        $property = new \ReflectionProperty(get_class($this->extension), 'offers');
        $property->setAccessible(true);

        return $property->getValue($this->extension);
    }

    /**
     * @param array $offers
     */
    protected function setOffers(array $offers = [])
    {
        $property = new \ReflectionProperty(get_class($this->extension), 'offers');
        $property->setAccessible(true);
        $property->setValue($this->extension, $offers);
    }
}
