<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\OrderBundle\Form\Section\SectionProvider;
use Oro\Bundle\ProductBundle\Storage\DataStorageInterface;
use Oro\Bundle\RFPBundle\Form\Extension\OrderLineItemDataStorageExtension;
use Oro\Bundle\RFPBundle\Storage\OffersFormStorage;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class OrderLineItemDataStorageExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrderLineItemDataStorageExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RequestStack */
    protected $requestStack;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DataStorageInterface */
    protected $sessionStorage;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OffersFormStorage */
    protected $formDataStorage;

    /** @var SectionProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $sectionProvider;

    /**
     * @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $featureChecker;

    protected function setUp()
    {
        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $this->sessionStorage = $this->getMockBuilder('Oro\Bundle\ProductBundle\Storage\DataStorageInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formDataStorage = $this->getMock('Oro\Bundle\RFPBundle\Storage\OffersFormStorage');

        $this->sectionProvider = $this->getMock('Oro\Bundle\OrderBundle\Form\Section\SectionProvider');

        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new OrderLineItemDataStorageExtension(
            $this->requestStack,
            $this->sessionStorage,
            $this->formDataStorage
        );
        $this->extension->setFeatureChecker($this->featureChecker);
        $this->extension->setSectionProvider($this->sectionProvider);
    }

    public function testGetExtendedType()
    {
        $this->assertInternalType('string', $this->extension->getExtendedType());
        $this->assertEquals('orob2b_order_line_item', $this->extension->getExtendedType());
    }

    public function testBuildViewNoFeatures()
    {
        $this->featureChecker->expects($this->never())
            ->method($this->anything());

        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $this->sectionProvider->expects($this->atLeastOnce())->method('addSections')
            ->with($this->extension->getExtendedType());

        $view = new FormView();
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->extension->buildView($view, $form, []);
    }

    public function testBuildViewWithFeatureEnabled()
    {
        $this->extension->addFeature('test');
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('test')
            ->willReturn(true);

        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $this->sectionProvider->expects($this->atLeastOnce())->method('addSections')
            ->with($this->extension->getExtendedType());

        $view = new FormView();
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->extension->buildView($view, $form, []);
    }

    public function testBuildFormWithoutParent()
    {
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $data = new \stdClass();

        $form->expects($this->any())->method('getData')->willReturn($data);
        $form->expects($this->any())->method('getParent')->willReturn(null);

        $this->sessionStorage->expects($this->never())->method('get');

        $this->extension->buildForm($this->getBuilderMock(true, $form), []);
    }

    public function testBuildFormWithoutParentData()
    {
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $parent = $this->getMock('Symfony\Component\Form\FormInterface');
        $data = new \stdClass();

        $form->expects($this->any())->method('getData')->willReturn($data);
        $parent->expects($this->any())->method('getData')->willReturn(null);
        $form->expects($this->any())->method('getParent')->willReturn($parent);

        $this->sessionStorage->expects($this->never())->method('get');

        $this->extension->buildForm($this->getBuilderMock(true, $form), []);
    }

    public function testBuildFormMissingOffer()
    {
        $offer = ['quantity' => 1, 'unit' => 'kg'];
        $this->setOffers([[$offer]]);

        $entity = new \stdClass();
        $entity->prop = 'value';

        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $parentForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())->method('getParent')->willReturn($parentForm);
        $form->expects($this->once())->method('getData')->willReturn($entity);
        $parentForm->expects($this->once())->method('getData')->willReturn(
            new ArrayCollection([new \stdClass(), $entity])
        );

        $this->sessionStorage->expects($this->never())->method('get');

        $this->extension->buildForm($this->getBuilderMock(true, $form), []);
    }

    public function testBuildFormWithWrongKey()
    {
        $entity = new \stdClass();

        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $parentForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())->method('getParent')->willReturn($parentForm);
        $form->expects($this->once())->method('getData')->willReturn($entity);
        $parentForm->expects($this->once())->method('getData')->willReturn(new ArrayCollection());

        $this->sessionStorage->expects($this->never())->method('get');

        $this->extension->buildForm($this->getBuilderMock(true, $form), []);
    }

    public function testBuildFormNotApplicable()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(null);

        $this->sessionStorage->expects($this->never())->method($this->anything());

        $this->extension->buildForm($this->getBuilderMock(), []);
    }

    public function testBuildFormMissingKey()
    {
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $this->sessionStorage->expects($this->never())->method('get');

        $this->extension->buildForm($this->getBuilderMock(true), []);

        $this->assertEquals(false, $this->getOffers());
    }


    public function testBuildFormWrongType()
    {
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $this->sessionStorage->expects($this->never())->method('get');

        $this->extension->buildForm($this->getBuilderMock(true), []);

        $this->assertEquals(false, $this->getOffers());
    }


    public function testBuildForm()
    {
        $offer = ['quantity' => 1, 'unit' => 'kg'];

        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())->method('get')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $this->sessionStorage->expects($this->once())->method('get')->willReturn([$offer]);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $parent = $this->getMock('Symfony\Component\Form\FormInterface');
        $data = new \stdClass();

        $form->expects($this->any())->method('getData')->willReturn($data);
        $parent->expects($this->any())->method('getData')->willReturn(new ArrayCollection([$data]));
        $form->expects($this->any())->method('getParent')->willReturn($parent);

        $this->extension->buildForm($this->getBuilderMock(true, $form), []);

        $this->assertEquals([$offer], $this->getOffers());
    }

    /**
     * @param bool $expectsAddEventListener
     * @param \PHPUnit_Framework_MockObject_MockObject|FormInterface $form
     * @return \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface
     */
    protected function getBuilderMock($expectsAddEventListener = false, FormInterface $form = null)
    {
        if (!$form) {
            $form = $this->getMock('Symfony\Component\Form\FormInterface');
        }

        if ($expectsAddEventListener) {
            $form->expects($this->atLeastOnce())->method('add')->with(
                $this->isType('string'),
                $this->isType('string'),
                $this->isType('array')
            );
        }

        /** @var  $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        if ($expectsAddEventListener) {
            $builder->expects($this->exactly(2))->method('addEventListener')->with(
                $this->isType('string'),
                $this->logicalAnd(
                    $this->isInstanceOf('\Closure'),
                    $this->callback(
                        function (\Closure $closure) use ($form) {
                            $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
                                ->disableOriginalConstructor()
                                ->getMock();

                            $event->expects($this->once())->method('getForm')->willReturn($form);
                            $event->expects($this->any())->method('getData')->willReturn([]);
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage "Oro\Bundle\OrderBundle\Form\Section\SectionProvider" expected, "NULL" given
     */
    public function testSectionProviderInvalid()
    {
        $extension = new OrderLineItemDataStorageExtension(
            $this->requestStack,
            $this->sessionStorage,
            $this->formDataStorage
        );

        $extension->setSectionProvider(new \stdClass());
    }

    public function testBuildFormDisabled()
    {
        $this->extension->addFeature('test');
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->never())->method('get');
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('test')
            ->willReturn(false);

        $this->sessionStorage->expects($this->never())->method($this->anything());

        $this->extension->buildForm($this->getBuilderMock(), []);
    }
}
