<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\DataTransformer;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\SaleBundle\Form\EventListener\QuoteToOrderResizeFormSubscriber;

class QuoteToOrderResizeFormSubscriberTest extends FormIntegrationTestCase
{
    const TYPE = 'form';

    /**
     * @var QuoteToOrderResizeFormSubscriber
     */
    protected $subscriber;

    protected function setUp()
    {
        parent::setUp();

        $this->subscriber = new QuoteToOrderResizeFormSubscriber(self::TYPE);
    }

    public function testPreSetDataEmpty()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->never())
            ->method('remove');
        $form->expects($this->never())
            ->method('add');

        $this->subscriber->preSetData(new FormEvent($form, null));
    }

    public function testPreSetData()
    {
        $form = $this->factory->create('collection', null, ['type' => 'text']);
        $form->setData(['test']);

        $data = ['first', 'second'];
        $this->subscriber->preSetData(new FormEvent($form, $data));

        $this->assertSameSize($data, $form);
        foreach ($data as $key => $value) {
            $this->assertTrue($form->has($key));
            $config = $form->get($key)->getConfig();
            $this->assertEquals(self::TYPE, $config->getType()->getName());
            $this->assertEquals(sprintf('[%s]', $key), $config->getOption('property_path'));
            $this->assertEquals($value, $config->getOption('data'));
        }
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "array or (\Traversable and \ArrayAccess)", "stdClass" given
     */
    public function testPreSetDataInvalid()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->subscriber->preSetData(new FormEvent($form, new \stdClass()));
    }
}
