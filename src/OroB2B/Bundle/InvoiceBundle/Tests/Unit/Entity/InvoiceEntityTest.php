<?php
namespace OroB2B\Bundle\InvoiceBundle\Tests\Unit\Entity;

use Symfony\Component\Validator\Context\ExecutionContext;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;
use OroB2B\Bundle\InvoiceBundle\Entity\InvoiceLineItem;

class InvoiceEntityTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['invoiceNumber', 'invoice-test'],
            ['owner', new User()],
            ['organization', new Organization()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
            ['invoiceDate', $now, false],
            ['paymentDueDate', $now, false],
            ['currency', 'USD'],
            ['poNumber', 'po-test'],
            ['account', new Account()],
            ['accountUser', new AccountUser()],
            ['subtotal', 12.55]
        ];

        $invoice = new Invoice();
        $this->assertPropertyAccessors($invoice, $properties);
        $this->assertPropertyCollection($invoice, 'lineItems', new InvoiceLineItem());
    }

    public function testRequireUpdate()
    {
        $invoice = new Invoice();
        $invoice->setUpdatedAt(new \DateTime());

        $invoice->requireUpdate();
        $this->assertNull($invoice->getUpdatedAt());
    }

    public function testValidatePaymentDueDateOnValid()
    {
        $invoice = new Invoice();
        $invoice->setPaymentDueDate(new \DateTime())
            ->setInvoiceDate(new \DateTime('-1 day'));

        $context = $this->getContextMock();
        $context->expects($this->never())
            ->method('buildViolation');

        $invoice->validatePaymentDueDate($context);
    }

    public function testValidatePaymentDueDateOnInvalid()
    {
        $invoice = new Invoice();
        $invoice->setPaymentDueDate(new \DateTime())
            ->setInvoiceDate(new \DateTime('+1 day'));

        $builder = $this->getBuilderMock();

        $builder->expects($this->once())
            ->method('atPath')
            ->with('paymentDueDate')
            ->willReturn($builder);

        $context = $this->getContextMock();

        $context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($builder);

        $invoice->validatePaymentDueDate($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExecutionContext $context
     */
    protected function getContextMock()
    {
        return $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContext')
            ->disableOriginalConstructor()
            ->setMethods(['buildViolation'])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getBuilderMock()
    {
        return $this->getMockBuilder('Symfony\Component\Validator\Violation\ConstraintViolationBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['addViolation', 'atPath'])
            ->getMock();
    }
}
