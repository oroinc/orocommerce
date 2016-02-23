<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Model\DTO;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Model\DTO\AccountWebsiteDTO;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountWebsiteDTOTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $website = new Website();
        $account = new Account();
        $object = new AccountWebsiteDTO($account, $website);

        $this->assertSame($website, $object->getWebsite());
        $this->assertSame($account, $object->getAccount());
    }
}
