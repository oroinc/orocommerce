<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\SaleBundle\Form\EventListener\QuoteToOrderResizeFormSubscriber;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class QuoteToOrderResizeFormSubscriberTest extends FormIntegrationTestCase
{
    /** @var QuoteToOrderResizeFormSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriber = new QuoteToOrderResizeFormSubscriber(FormType::class);
    }

    public function testPreSetDataEmpty()
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->never())
            ->method('remove');
        $form->expects($this->never())
            ->method('add');

        $this->subscriber->preSetData(new FormEvent($form, null));
    }

    public function testPreSetData()
    {
        $form = $this->factory->create(CollectionType::class, null, ['entry_type' => TextType::class]);
        $form->setData(['test']);

        $data = ['first', 'second'];
        $this->subscriber->preSetData(new FormEvent($form, $data));

        $this->assertSameSize($data, $form);
        foreach ($data as $key => $value) {
            $this->assertTrue($form->has($key));
            $config = $form->get($key)->getConfig();
            $this->assertInstanceOf(FormType::class, $config->getType()->getInnerType());
            $this->assertEquals(sprintf('[%s]', $key), $config->getOption('property_path'));
            $this->assertEquals($value, $config->getOption('data'));
        }
    }

    public function testPreSetDataInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "array or (\Traversable and \ArrayAccess)", "stdClass" given'
        );

        $form = $this->createMock(FormInterface::class);

        $this->subscriber->preSetData(new FormEvent($form, new \stdClass()));
    }
}
