<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\OrderBundle\Tests\Unit\Manager\AbstractAddressManagerTest;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteAddress;
use OroB2B\Bundle\SaleBundle\Model\QuoteAddressManager;
use OroB2B\Bundle\SaleBundle\Provider\QuoteAddressProvider;

class QuoteAddressManagerTest extends AbstractAddressManagerTest
{
    /** @var QuoteAddressManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|QuoteAddressProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->provider = $this->getMockBuilder('OroB2B\Bundle\SaleBundle\Provider\QuoteAddressProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->manager = new QuoteAddressManager(
            $this->provider,
            $this->registry,
            'OroB2B\Bundle\SaleBundle\Entity\QuoteAddress'
        );
    }

    protected function tearDown()
    {
        unset($this->manager, $this->provider, $this->registry);
    }

    /**
     * @param AbstractAddress $address
     * @param QuoteAddress|null $expected
     * @param AbstractAddress|null $expectedAccountAddress
     * @param AbstractAddress|null $expectedAccountUserAddress
     * @param QuoteAddress|null $quoteAddress
     *
     * @dataProvider quoteDataProvider
     */
    public function testUpdateFromAbstract(
        AbstractAddress $address,
        QuoteAddress $expected = null,
        AbstractAddress $expectedAccountAddress = null,
        AbstractAddress $expectedAccountUserAddress = null,
        QuoteAddress $quoteAddress = null
    ) {
        $classMetadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $classMetadata->expects($this->once())->method('getFieldNames')->willReturn(['street', 'city', 'label']);
        $classMetadata->expects($this->once())->method('getAssociationNames')
            ->willReturn(['country', 'region']);

        $em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $em->expects($this->once())->method('getClassMetadata')->willReturn($classMetadata);

        $this->registry->expects($this->any())->method('getManagerForClass')->with($this->isType('string'))
            ->willReturn($em);

        $quoteAddress = $this->manager->updateFromAbstract($address, $quoteAddress);
        $this->assertEquals($expected, $quoteAddress);
        $this->assertEquals($expectedAccountAddress, $quoteAddress->getAccountAddress());
        $this->assertEquals($expectedAccountUserAddress, $quoteAddress->getAccountUserAddress());
    }

    /**
     * @return array
     */
    public function quoteDataProvider()
    {
        $country = new Country('US');
        $region = new Region('US-AL');

        return [
            'empty account address' => [
                $accountAddress = new AccountAddress(),
                (new QuoteAddress())
                    ->setAccountAddress($accountAddress),
                $accountAddress
            ],
            'empty account user address' => [
                $accountUserAddress = new AccountUserAddress(),
                (new QuoteAddress())
                    ->setAccountUserAddress($accountUserAddress),
                null,
                $accountUserAddress
            ],
            'from account address' => [
                $accountAddress = (new AccountAddress())
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                (new QuoteAddress())
                    ->setAccountAddress($accountAddress)
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                $accountAddress
            ],
            'from account user address' => [
                $accountUserAddress = (new AccountUserAddress())
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                (new QuoteAddress())
                    ->setAccountUserAddress($accountUserAddress)
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                null,
                $accountUserAddress
            ],
            'do not override value from existing with empty one' => [
                $accountUserAddress = (new AccountUserAddress())
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                (new QuoteAddress())
                    ->setAccountUserAddress($accountUserAddress)
                    ->setLabel('ExistingLabel')
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                null,
                $accountUserAddress,
                (new QuoteAddress())
                    ->setLabel('ExistingLabel')
            ],
        ];
    }

    /**
     * @param Quote $quote
     * @param array $accountAddresses
     * @param array $accountUserAddresses
     * @param array $expected
     *
     * @dataProvider groupedAddressDataProvider
     */
    public function testGetGroupedAddresses(
        Quote $quote,
        array $accountAddresses = [],
        array $accountUserAddresses = [],
        array $expected = []
    ) {
        $this->provider->expects($this->any())->method('getAccountAddresses')->willReturn($accountAddresses);
        $this->provider->expects($this->any())->method('getAccountUserAddresses')->willReturn($accountUserAddresses);

        $this->manager->addEntity('au', 'OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress');
        $this->manager->addEntity('a', 'OroB2B\Bundle\AccountBundle\Entity\AccountAddress');

        $this->assertEquals($expected, $this->manager->getGroupedAddresses($quote, AddressType::TYPE_BILLING));
    }

    /**
     * @return array
     */
    public function groupedAddressDataProvider()
    {
        return [
            'empty account user' => [new Quote()],
            'empty account' => [
                (new Quote())->setAccountUser(new AccountUser()),
                [],
                [
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress', 1),
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress', 2),
                ],
                [
                    QuoteAddressManager::ACCOUNT_USER_LABEL => [
                        'au_1' => $this->getEntity(
                            'OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress',
                            1
                        ),
                        'au_2' => $this->getEntity(
                            'OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress',
                            2
                        ),
                    ],
                ],
            ],
            'account' => [
                (new Quote())->setAccountUser(new AccountUser())->setAccount(new Account()),
                [
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountAddress', 1),
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountAddress', 2),
                ],
                [],
                [
                    QuoteAddressManager::ACCOUNT_LABEL => [
                        'a_1' => $this->getEntity(
                            'OroB2B\Bundle\AccountBundle\Entity\AccountAddress',
                            1
                        ),
                        'a_2' => $this->getEntity(
                            'OroB2B\Bundle\AccountBundle\Entity\AccountAddress',
                            2
                        ),
                    ],
                ],
            ],
            'full' => [
                (new Quote())->setAccountUser(new AccountUser())->setAccount(new Account()),
                [
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountAddress', 1),
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountAddress', 2),
                ],
                [
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress', 1),
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress', 2),
                ],
                [
                    QuoteAddressManager::ACCOUNT_LABEL => [
                        'a_1' => $this->getEntity(
                            'OroB2B\Bundle\AccountBundle\Entity\AccountAddress',
                            1
                        ),
                        'a_2' => $this->getEntity(
                            'OroB2B\Bundle\AccountBundle\Entity\AccountAddress',
                            2
                        ),
                    ],
                    QuoteAddressManager::ACCOUNT_USER_LABEL => [
                        'au_1' => $this->getEntity(
                            'OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress',
                            1
                        ),
                        'au_2' => $this->getEntity(
                            'OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress',
                            2
                        ),
                    ],
                ],
            ],
        ];
    }
}
