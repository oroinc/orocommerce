<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Entity;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestAdditionalNote;

class RequestAdditionalNoteTest extends AbstractTest
{
    /** @var RequestAdditionalNote */
    protected $requestAdditionalNote;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->requestAdditionalNote = new RequestAdditionalNote();
    }

    public function testProperties()
    {
        $properties = [
            ['id', 123],
            ['request', new Request()],
            ['type', RequestAdditionalNote::TYPE_CUSTOMER_NOTE],
            ['author', 'author'],
            ['userId', 234],
            ['text', 'text'],
            ['createdAt', new \DateTime(), false],
            ['updatedAt', new \DateTime(), false],
        ];

        $this->assertPropertyAccessors($this->requestAdditionalNote, $properties);
    }

    public function testGetAllowedTypes()
    {
        $this->assertEquals(
            [RequestAdditionalNote::TYPE_CUSTOMER_NOTE, RequestAdditionalNote::TYPE_SELLER_NOTE],
            $this->requestAdditionalNote->getAllowedTypes()
        );
    }

    public function testIsTypeAllowed()
    {
        $this->assertTrue($this->requestAdditionalNote->isTypeAllowed(RequestAdditionalNote::TYPE_CUSTOMER_NOTE));
        $this->assertTrue($this->requestAdditionalNote->isTypeAllowed(RequestAdditionalNote::TYPE_SELLER_NOTE));

        $this->assertFalse($this->requestAdditionalNote->isTypeAllowed('unknown type'));
    }

    public function testSetTypeWithException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Type "unknown type" is not allowed');

        $this->requestAdditionalNote->setType('unknown type');
    }
}
