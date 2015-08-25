<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class PaymentTermStub extends PaymentTerm
{
    protected $id;

    public function __construct($id, $accounts = [], $accountGroups = [])
    {
        $this->id = $id;
        $this->accounts = new ArrayCollection($accounts);
        $this->accountGroups = new ArrayCollection($accountGroups);
    }
}
