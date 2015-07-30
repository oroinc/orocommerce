<?php


namespace OroB2B\src\OroB2B\Bundle\PaymentBundle\Tests\Unit\Fixtures;


use Doctrine\Common\Collections\ArrayCollection;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class PaymentTermStub extends PaymentTerm
{
    protected $id;

    public function __construct($id, $customers = [], $customerGroups = [])
    {
        $this->id = $id;
        $this->customers = new ArrayCollection($customers);
        $this->customerGroups = new ArrayCollection($customerGroups);
    }
}