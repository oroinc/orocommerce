<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\FormTypeStub;
use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;
use Oro\Bundle\SaleBundle\Form\EventListener\QuoteProductKitItemLineItemExistingCollectionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

class QuoteProductKitItemLineItemExistingCollectionListenerTest extends TestCase
{
    private QuoteProductKitItemLineItemExistingCollectionListener $listener;

    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->listener = new QuoteProductKitItemLineItemExistingCollectionListener(
            FormTypeStub::class,
            ['currency' => 'USD']
        );

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addType(new FormTypeStub(['currency']))
            ->getFormFactory();
    }

    public function testOnPreSetDataWhenNoData(): void
    {
        $formBuilder = $this->formFactory->createBuilder(FormType::class, [])
            ->add('1', FormTypeStub::class, ['required' => true, 'currency' => 'USD'])
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertCount(1, $form);
        self::assertTrue($form->get('1')->isRequired());
        self::assertEquals('USD', $form->get('1')->getConfig()->getOption('currency'));
    }

    public function testOnPreSetDataWhenHasKitItemLineItemAndNotOptional(): void
    {
        $kitItemLineItem1 = (new QuoteProductKitItemLineItem())->setOptional(false);
        $collection = new PersistentCollection(
            $this->createMock(EntityManagerInterface::class),
            new ClassMetadata(QuoteProductKitItemLineItem::class),
            new ArrayCollection(['1' => $kitItemLineItem1])
        );
        $formBuilder = $this->formFactory->createBuilder(FormType::class, $collection)
            ->add('1', FormTypeStub::class, ['required' => true, 'currency' => 'USD', 'property_path' => '[1]'])
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertTrue($form->get('1')->isRequired());
        self::assertEquals('USD', $form->get('1')->getConfig()->getOption('currency'));
    }

    public function testOnPreSetDataWhenHasKitItemLineItemAndOptional(): void
    {
        $kitItemLineItem1 = (new QuoteProductKitItemLineItem())->setOptional(true);
        $collection = new PersistentCollection(
            $this->createMock(EntityManagerInterface::class),
            new ClassMetadata(QuoteProductKitItemLineItem::class),
            new ArrayCollection(['1' => $kitItemLineItem1])
        );
        $formBuilder = $this->formFactory->createBuilder(FormType::class, $collection)
            ->add('1', FormTypeStub::class, ['required' => true, 'currency' => 'USD', 'property_path' => '[1]'])
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertTrue($form->get('1')->isRequired());
        self::assertEquals('USD', $form->get('1')->getConfig()->getOption('currency'));
    }

    public function testOnPreSetDataWhenNoCorrespondingKitItemLineItem(): void
    {
        $kitItemLineItem2 = (new QuoteProductKitItemLineItem())->setOptional(false);
        $collection = new PersistentCollection(
            $this->createMock(EntityManagerInterface::class),
            new ClassMetadata(QuoteProductKitItemLineItem::class),
            new ArrayCollection(['2' => $kitItemLineItem2])
        );
        $formBuilder = $this->formFactory->createBuilder(FormType::class, $collection)
            ->add('1', FormTypeStub::class, ['required' => true, 'currency' => 'USD', 'property_path' => '[1]'])
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertCount(2, $form);

        self::assertTrue($form->get('1')->isRequired());
        self::assertEquals('USD', $form->get('1')->getConfig()->getOption('currency'));

        self::assertFalse($form->get('2')->isRequired());
        self::assertEquals('[2]', $form->get('2')->getConfig()->getOption('property_path'));
        self::assertEquals('USD', $form->get('2')->getConfig()->getOption('currency'));
    }

    public function testOnPreSetDataWhenNotPersistentCollectionAndHasKitItemLineItemAndNotOptional(): void
    {
        $kitItemLineItem1 = (new QuoteProductKitItemLineItem())->setOptional(false);
        $arrayCollection = new ArrayCollection(['1' => $kitItemLineItem1]);
        $formBuilder = $this->formFactory->createBuilder(FormType::class, $arrayCollection)
            ->add('1', FormTypeStub::class, ['required' => true, 'currency' => 'USD', 'property_path' => '[1]'])
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertTrue($form->get('1')->isRequired());
        self::assertEquals('USD', $form->get('1')->getConfig()->getOption('currency'));
    }

    public function testOnPreSetDataWhenNotPersistentCollectionAndHasKitItemLineItemAndOptional(): void
    {
        $kitItemLineItem1 = (new QuoteProductKitItemLineItem())->setOptional(true);
        $arrayCollection = new ArrayCollection(['1' => $kitItemLineItem1]);
        $formBuilder = $this->formFactory->createBuilder(FormType::class, $arrayCollection)
            ->add('1', FormTypeStub::class, ['required' => true, 'currency' => 'USD', 'property_path' => '[1]'])
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertTrue($form->get('1')->isRequired());
        self::assertEquals('USD', $form->get('1')->getConfig()->getOption('currency'));
    }

    public function testOnPreSetDataWhenNotPersistentCollectionAndNoCorrespondingKitItemLineItem(): void
    {
        $kitItemLineItem2 = (new QuoteProductKitItemLineItem())->setOptional(false);
        $arrayCollection = new ArrayCollection(['2' => $kitItemLineItem2]);
        $formBuilder = $this->formFactory->createBuilder(FormType::class, $arrayCollection)
            ->add('1', FormTypeStub::class, ['required' => true, 'currency' => 'USD', 'property_path' => '[1]'])
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertCount(2, $form);

        self::assertTrue($form->get('1')->isRequired());
        self::assertEquals('USD', $form->get('1')->getConfig()->getOption('currency'));

        self::assertFalse($form->get('2')->isRequired());
        self::assertEquals('[2]', $form->get('2')->getConfig()->getOption('property_path'));
        self::assertEquals('USD', $form->get('2')->getConfig()->getOption('currency'));
    }
}
