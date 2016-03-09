<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Formatter;

use Oro\Bundle\EntityBundle\Provider\ChainEntityClassNameProvider;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Formatter\SourceDocumentFormatter;

class SourceDocumentFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SourceDocumentFormatter
     */
    protected $sourceDocumentFormatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ChainEntityClassNameProvider
     */
    protected $chainEntityClassNameProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->chainEntityClassNameProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\Provider\ChainEntityClassNameProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->sourceDocumentFormatter = new SourceDocumentFormatter(
            $this->chainEntityClassNameProvider
        );
    }

    /**
     * @dataProvider getProvider
     *
     * @param $entity
     * @param $className
     * @param $expectedFormat
     */
    public function testFormat($entity, $className, $expectedFormat)
    {
        $this->chainEntityClassNameProvider
            ->expects($this->once())
            ->method('getEntityClassName')
            ->willReturn($className);

        $response = $this->sourceDocumentFormatter->format($entity);

        self::assertEquals($expectedFormat, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function getProvider()
    {
        return [
            'order without identifier' => [
                'entity' => $this->getOrder(),
                'className' => 'Order',
                'expectedFormat' => 'Order'
            ],
            'order with identifier' => [
                'entity' => $this->getOrder('FR1012401'),
                'className' => 'Order',
                'expectedFormat' => 'Order FR1012401'
            ]
        ];
    }

    /**
     * @param null $identifier
     *
     * @return Order
     */
    protected function getOrder($identifier = null)
    {
        $order = new Order();

        if ($identifier) {
            $order->setIdentifier($identifier);
        }

        return $order;
    }
}
