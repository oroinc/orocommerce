<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Provider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Layout\LayoutContext;

use OroB2B\Bundle\AccountBundle\Provider\NewAccountUserDataProvider;

/**
 * @dbIsolation
 */
class NewAccountUserDataProviderTest extends WebTestCase
{
    /** @var LayoutContext */
    protected $context;

    /** @var NewAccountUserDataProvider */
    protected $dataProvider;

    protected function setUp()
    {
        $this->initClient();

        $this->context = new LayoutContext();
        $this->dataProvider = $this->getContainer()->get('orob2b_account.layout.data_provider.new_account_user');
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('orob2b_account_new_account_user', $this->dataProvider->getIdentifier());
    }

    public function testGetData()
    {
        $actual = $this->dataProvider->getData($this->context);

        $this->assertInstanceOf('OroB2B\Bundle\AccountBundle\Entity\AccountUser', $actual);
        $this->assertEquals(null, $actual->getId()); // new AccountUser
        $this->assertNotEmpty($actual->getRoles()); // has at least one Role
        $this->assertNotEquals(null, $actual->getOwner()); // has Owner
        $this->assertNotEquals(null, $actual->getOrganization()); // has Organization
    }
}
