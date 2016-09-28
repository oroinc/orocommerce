<?php

namespace Oro\Bundle\AccountBundle\Tests\Selenium\Entity;

class SeleniumAddressBookTestInput
{
    /**
     * @return array
     */
    protected function addressBookTestProvider()
    {
        return [
            [$this->getAccountUsers(self::USER1), 1, 1, false, false, true, true, true, true, true, true],
            [$this->getAccountUsers(self::USER1), 8, 8, true, true, true, true, true, true, true, true],
        ];
    }
}
