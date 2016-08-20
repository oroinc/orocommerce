<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Fixtures\Stub;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;

class PaymentTermStub extends PaymentTerm
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @param int   $id
     * @param array $accounts
     * @param array $accountGroups
     */
    public function __construct($id, $accounts = [], $accountGroups = [])
    {
        $this->id = $id;
        $this->accounts = new ArrayCollection($accounts);
        $this->accountGroups = new ArrayCollection($accountGroups);
    }
}
