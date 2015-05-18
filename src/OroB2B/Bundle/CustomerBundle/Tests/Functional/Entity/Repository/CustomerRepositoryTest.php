<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\Repository\CustomerRepository;

/**
 * @dbIsolation
 */
class CustomerRepositoryTest extends WebTestCase
{
    /**
     * @var CustomerRepository
     */
    protected $repository;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BCustomerBundle:Customer');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers'
            ]
        );

        $this->aclHelper = $this->getContainer()->get('oro_security.acl_helper');
    }

    /**
     * @dataProvider customerReferencesDataProvider
     * @param string $referenceName
     * @param array $expectedReferences
     */
    public function testGetChildrenIds($referenceName, array $expectedReferences)
    {
        /** @var Customer $customer */
        $customer = $this->getReference($referenceName);

        $expected = [];
        foreach ($expectedReferences as $reference) {
            $expected[] = $this->getReference($reference)->getId();
        }
        $childrenIds = $this->repository->getChildrenIds($this->aclHelper, $customer->getId());
        sort($expected);
        sort($childrenIds);

        $this->assertEquals($expected, $childrenIds);
    }

    /**
     * @return array
     */
    public function customerReferencesDataProvider()
    {
        return [
            'orphan' => ['orphan', []],
            'level_1' => ['level_1', ['level_1.1', 'level_1.1.1', 'level_1.2']],
            'level_1.1' => ['level_1.1', ['level_1.1.1']],
            ['level_1.2', []]
        ];
    }
}
