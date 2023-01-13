<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrderBundle\Form\Section\SectionProvider;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use Oro\Bundle\ProductBundle\Storage\DataStorageInterface;
use Oro\Bundle\RFPBundle\Form\Extension\OrderLineItemDataStorageExtension;
use Oro\Bundle\RFPBundle\Storage\OffersFormStorage;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrderLineItemDataStorageExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|RequestStack */
    private $requestStack;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DataStorageInterface */
    private $sessionStorage;

    /** @var \PHPUnit\Framework\MockObject\MockObject|OffersFormStorage */
    private $formDataStorage;

    /** @var SectionProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $sectionProvider;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var OrderLineItemDataStorageExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->sessionStorage = $this->createMock(DataStorageInterface::class);
        $this->formDataStorage = $this->createMock(OffersFormStorage::class);
        $this->sectionProvider = $this->createMock(SectionProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->extension = new OrderLineItemDataStorageExtension(
            $this->requestStack,
            $this->sessionStorage,
            $this->formDataStorage
        );
        $this->extension->setFeatureChecker($this->featureChecker);
        $this->extension->setSectionProvider($this->sectionProvider);
    }

    public function testGetExtendedTypes()
    {
        $this->assertEquals([OrderLineItemType::class], OrderLineItemDataStorageExtension::getExtendedTypes());
    }

    public function testBuildViewNoFeatures()
    {
        $this->featureChecker->expects($this->never())
            ->method($this->anything());

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->willReturn(true);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->sectionProvider->expects($this->atLeastOnce())
            ->method('addSections')
            ->with(OrderLineItemType::class);

        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $this->extension->buildView($view, $form, []);
    }

    public function testBuildViewWithFeatureEnabled()
    {
        $this->extension->addFeature('test');
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('test')
            ->willReturn(true);

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->willReturn(true);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->sectionProvider->expects($this->atLeastOnce())
            ->method('addSections')
            ->with(OrderLineItemType::class);

        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $this->extension->buildView($view, $form, []);
    }

    public function testBuildFormWithoutParent()
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->willReturn(true);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $form = $this->createMock(FormInterface::class);
        $data = new \stdClass();

        $form->expects($this->any())
            ->method('getData')
            ->willReturn($data);
        $form->expects($this->any())
            ->method('getParent')
            ->willReturn(null);

        $this->sessionStorage->expects($this->never())
            ->method('get');

        $this->extension->buildForm($this->getFormBuilder(true, $form), []);
    }

    public function testBuildFormWithoutParentData()
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->willReturn(true);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $form = $this->createMock(FormInterface::class);
        $parent = $this->createMock(FormInterface::class);
        $data = new \stdClass();

        $form->expects($this->any())
            ->method('getData')
            ->willReturn($data);
        $parent->expects($this->any())
            ->method('getData')
            ->willReturn(null);
        $form->expects($this->any())
            ->method('getParent')
            ->willReturn($parent);

        $this->sessionStorage->expects($this->never())
            ->method('get');

        $this->extension->buildForm($this->getFormBuilder(true, $form), []);
    }

    public function testBuildFormMissingOffer()
    {
        $offer = ['quantity' => 1, 'unit' => 'kg'];
        $this->setOffers([[$offer]]);

        $entity = new \stdClass();
        $entity->prop = 'value';

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->willReturn(true);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $form = $this->createMock(FormInterface::class);
        $parentForm = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm);
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($entity);
        $parentForm->expects($this->once())
            ->method('getData')
            ->willReturn(new ArrayCollection([new \stdClass(), $entity]));

        $this->sessionStorage->expects($this->never())
            ->method('get');

        $this->extension->buildForm($this->getFormBuilder(true, $form), []);
    }

    public function testBuildFormWithWrongKey()
    {
        $entity = new \stdClass();

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->willReturn(true);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $form = $this->createMock(FormInterface::class);
        $parentForm = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm);
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($entity);
        $parentForm->expects($this->once())
            ->method('getData')
            ->willReturn(new ArrayCollection());

        $this->sessionStorage->expects($this->never())
            ->method('get');

        $this->extension->buildForm($this->getFormBuilder(true, $form), []);
    }

    public function testBuildFormNotApplicable()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->sessionStorage->expects($this->never())
            ->method($this->anything());

        $this->extension->buildForm($this->getFormBuilder(), []);
    }

    public function testBuildFormMissingKey()
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->willReturn(true);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->sessionStorage->expects($this->never())
            ->method('get');

        $this->extension->buildForm($this->getFormBuilder(true), []);

        $this->assertEquals(false, $this->getOffers());
    }

    public function testBuildFormWrongType()
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->willReturn(true);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->sessionStorage->expects($this->never())
            ->method('get');

        $this->extension->buildForm($this->getFormBuilder(true), []);

        $this->assertEquals(false, $this->getOffers());
    }

    public function testBuildForm()
    {
        $offer = ['quantity' => 1, 'unit' => 'kg'];

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->willReturn(true);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->sessionStorage->expects($this->once())
            ->method('get')
            ->willReturn([$offer]);

        $form = $this->createMock(FormInterface::class);
        $parent = $this->createMock(FormInterface::class);
        $data = new \stdClass();

        $form->expects($this->any())
            ->method('getData')
            ->willReturn($data);
        $parent->expects($this->any())
            ->method('getData')
            ->willReturn(new ArrayCollection([$data]));
        $form->expects($this->any())
            ->method('getParent')
            ->willReturn($parent);

        $this->extension->buildForm($this->getFormBuilder(true, $form), []);

        $this->assertEquals([$offer], $this->getOffers());
    }

    private function getFormBuilder(
        bool $expectsAddEventListener = false,
        FormInterface|\PHPUnit\Framework\MockObject\MockObject $form = null
    ): FormBuilderInterface {
        if (!$form) {
            $form = $this->createMock(FormInterface::class);
        }

        if ($expectsAddEventListener) {
            $form->expects($this->atLeastOnce())
                ->method('add')
                ->with($this->isType('string'), $this->isType('string'), $this->isType('array'));
        }

        $builder = $this->createMock(FormBuilderInterface::class);
        if ($expectsAddEventListener) {
            $builder->expects($this->exactly(2))
                ->method('addEventListener')
                ->with(
                    $this->isType('string'),
                    $this->logicalAnd(
                        $this->isInstanceOf(\Closure::class),
                        $this->callback(function (\Closure $closure) use ($form) {
                            $event = $this->createMock(FormEvent::class);
                            $event->expects($this->once())
                                ->method('getForm')
                                ->willReturn($form);
                            $event->expects($this->any())
                                ->method('getData')
                                ->willReturn([]);
                            $closure($event);

                            return true;
                        })
                    )
                );
        } else {
            $builder->expects($this->never())
                ->method('addEventListener');
        }

        return $builder;
    }

    private function getOffers(): array|bool
    {
        return ReflectionUtil::getPropertyValue($this->extension, 'offers');
    }

    private function setOffers(array $offers = []): void
    {
        ReflectionUtil::setPropertyValue($this->extension, 'offers', $offers);
    }

    public function testSectionProviderInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"Oro\Bundle\OrderBundle\Form\Section\SectionProvider" expected, "NULL" given');

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
        $request = $this->createMock(Request::class);
        $request->expects($this->never())
            ->method('get');
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('test')
            ->willReturn(false);

        $this->sessionStorage->expects($this->never())
            ->method($this->anything());

        $this->extension->buildForm($this->getFormBuilder(), []);
    }
}
