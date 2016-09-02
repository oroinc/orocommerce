<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Event;

use Oro\Bundle\PaymentBundle\Event\AbstractExtractOptionsEvent;

class AbstractExtractOptionsEventTestCase extends \PHPUnit_Framework_TestCase
{
    /** @var array */
    protected $keys = ['key1', 'key2', 'key3'];

    /** @var array */
    protected $options = [];

    /** @var AbstractExtractOptionsEvent */
    protected $event;

    public function testApplyKeys()
    {
        $expected = [
            'key1' => 'option11',
            'key2' => 'option2',
            'key3' => 'option3'
        ];
        $this->event->setOptions($this->event->applyKeys($expected));
        $result = $this->event->getOptions();
        $this->assertEquals($expected, $result);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Different number of keys and passed options was expected
     */
    public function testApplyKeysException()
    {
        $this->event->setOptions($this->event->applyKeys(['option1']));
    }
}
