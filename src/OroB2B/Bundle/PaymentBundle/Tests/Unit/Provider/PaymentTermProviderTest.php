<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class PaymentTermProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $paymentTermClass = 'testClass';

    /**
     * @var PaymentTermProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->provider = new PaymentTermProvider($this->registry, $this->paymentTermClass);
    }

    protected function tearDown()
    {
        unset($this->registry, $this->paymentTermClass, $this->provider);
    }

    /**
     * @dataProvider getPaymentTermDataProvider
     *
     * @param Account $account
     * @param array $repositoryMethods
     * @param PaymentTerm|null $expected
     */
    public function testGetPaymentTerm(Account $account, array $repositoryMethods, PaymentTerm $expected = null)
    {
        $repository = $this->assertPaymentTermRepositoryCall();

        foreach ($repositoryMethods as $methodName => $methodParams) {
            $invocationMocker = $repository->expects($methodParams['expects'])
                ->method($methodName);

            if (array_key_exists('willReturn', $methodParams)) {
                $invocationMocker->with($methodParams['with'])
                    ->willReturn($methodParams['willReturn']);
            }
        }

        $this->assertEquals($expected, $this->provider->getPaymentTerm($account));
    }

    /**
     * @return array
     */
    public function getPaymentTermDataProvider()
    {
        $account = new Account();

        $accountWithGroup = new Account();
        $accountWithGroup->setGroup(new AccountGroup());

        $paymentTerm = new PaymentTerm();

        return [
            [
                'account' => $account,
                'repositoryMethods' => [
                    'getOnePaymentTermByAccount' => [
                        'expects' => $this->once(),
                        'with' => $account,
                        'willReturn' => $paymentTerm,
                    ],
                    'getOnePaymentTermByAccountGroup' => [
                        'expects' => $this->never(),
                    ]
                ],
                'expected' => $paymentTerm
            ],
            [
                'account' => $account,
                'repositoryMethods' => [
                    'getOnePaymentTermByAccount' => [
                        'expects' => $this->once(),
                        'with' => $account,
                        'willReturn' => null,
                    ],
                    'getOnePaymentTermByAccountGroup' => [
                        'expects' => $this->never()
                    ]
                ],
                'expected' => null
            ],
            [
                'account' => $accountWithGroup,
                'repositoryMethods' => [
                    'getOnePaymentTermByAccount' => [
                        'expects' => $this->once(),
                        'with' => $accountWithGroup,
                        'willReturn' => null,
                    ],
                    'getOnePaymentTermByAccountGroup' => [
                        'expects' => $this->once(),
                        'with' => $accountWithGroup->getGroup(),
                        'willReturn' => $paymentTerm,
                    ]
                ],
                'expected' => $paymentTerm
            ],
            [
                'account' => $accountWithGroup,
                'repositoryMethods' => [
                    'getOnePaymentTermByAccount' => [
                        'expects' => $this->once(),
                        'with' => $accountWithGroup,
                        'willReturn' => null,
                    ],
                    'getOnePaymentTermByAccountGroup' => [
                        'expects' => $this->once(),
                        'with' => $accountWithGroup->getGroup(),
                        'willReturn' => null,
                    ]
                ],
                'expected' => null
            ]
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function assertPaymentTermRepositoryCall()
    {
        $repository = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $manager->expects($this->any())
            ->method('getRepository')
            ->with($this->paymentTermClass)
            ->willReturn($repository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with($this->paymentTermClass)
            ->willReturn($manager);

        return $repository;
    }
}
