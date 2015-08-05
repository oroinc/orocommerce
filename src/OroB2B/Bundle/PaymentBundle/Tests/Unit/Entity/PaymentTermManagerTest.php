<?php
//
//namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Entity;
//
//use OroB2B\Bundle\AccountBundle\Entity\Account;
//use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
//use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
//use OroB2B\Bundle\PaymentBundle\Entity\PaymentTermManager;
//
//class PaymentTermManagerTest extends \PHPUnit_Framework_TestCase
//{
//    const PAYMENT_TERM_CLASS = 'OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm';
//    const DEFAULT_DELETE_MESSAGE_TEXT = 'Delete message text';
//    const ADDITIONAL_TEXT_WITH_TWO_LINKS = ' with two links';
//    const ADDITIONAL_TEXT_FOR_ACCOUNT_ONLY = ' with account link';
//    const ADDITIONAL_TEXT_FOR_ACCOUNT_GROUP_ONLY = ' with account link';
//
//    /**
//     * @var \PHPUnit_Framework_MockObject_MockObject
//     */
//    protected $om;
//
//    /**
//     * @var \PHPUnit_Framework_MockObject_MockObject
//     */
//    protected $translator;
//
//    /**
//     * @var \PHPUnit_Framework_MockObject_MockObject
//     */
//    protected $router;
//
//    /**
//     * @var PaymentTermManager
//     */
//    protected $paymentTermManager;
//
//    /**
//     * @var \PHPUnit_Framework_MockObject_MockObject
//     */
//    protected $accountRepository;
//
//    /**
//     * @var \PHPUnit_Framework_MockObject_MockObject
//     */
//    protected $accountGroupRepository;
//
//    protected function setUp()
//    {
//        $this->om = $this->getMock('\Doctrine\Common\Persistence\ObjectManager');
//        $this->translator = $this
//            ->getMockBuilder('Symfony\Component\Translation\Translator')
//            ->disableOriginalConstructor()
//            ->getMock();
//        $this->router = $this
//            ->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
//            ->disableOriginalConstructor()
//            ->getMock();
//
//        $this->paymentTermManager = new PaymentTermManager($this->om, $this->translator, $this->router);
//
//        $this->accountRepository = $this
//            ->getMockBuilder('\Doctrine\ORM\EntityRepository')
//            ->disableOriginalConstructor()
//            ->getMock();
//
//        $this->accountGroupRepository = $this
//            ->getMockBuilder('\Doctrine\ORM\EntityRepository')
//            ->disableOriginalConstructor()
//            ->getMock();
//    }
//
//    /**
//     * @param $account
//     * @param $accountGroup
//     * @param $expectedData
//     * @dataProvider deleteTextDataProvider
//     */
//    public function testGetDeleteMessageText($account, $accountGroup, $expectedData)
//    {
//        $paymentTerm = new PaymentTerm();
//
//        $this->accountRepository->expects($this->any())
//            ->method('findBy')
//            ->with(['paymentTerm' => $paymentTerm])
//            ->will($this->returnValue($account));
//
//        $this->accountGroupRepository->expects($this->any())
//            ->method('findBy')
//            ->with(['paymentTerm' => $paymentTerm])
//            ->will($this->returnValue($accountGroup));
//
//        $this->om->expects($this->at(0))
//            ->method('getRepository')
//            ->with($this->equalTo('OroB2BAccountBundle:AccountGroup'))
//            ->will($this->returnValue($this->accountGroupRepository));
//
//        $this->om->expects($this->at(1))
//            ->method('getRepository')
//            ->with($this->equalTo('OroB2BAccountBundle:Account'))
//            ->will($this->returnValue($this->accountRepository));
//
//        $this->translator->expects($this->any())
//            ->method('trans')
//            ->will($this->returnCallback(function ($name, $additional) {
//                switch ($name) {
//                    case 'oro.ui.delete_confirm':
//                        return static::DEFAULT_DELETE_MESSAGE_TEXT;
//                    case 'orob2b.payment.controller.paymentterm.delete.with_two_url.message':
//                        return static::ADDITIONAL_TEXT_WITH_TWO_LINKS;
//                    case 'orob2b.payment.controller.paymentterm.delete.with_url.message':
//                        if (strstr($additional['%url%'], 'ACCOUNT_ONLY') !== false) {
//                            return static::ADDITIONAL_TEXT_FOR_ACCOUNT_ONLY;
//                        }
//                        return static::ADDITIONAL_TEXT_FOR_ACCOUNT_GROUP_ONLY;
//                    case 'orob2b.account.accountgroup.entity_plural_label':
//                        return 'ACCOUNT_GROUP';
//                    case 'orob2b.account.entity_plural_label':
//                        return 'ACCOUNT_ONLY';
//                    default:
//                        return '';
//                }
//            }));
//
//        $this->assertEquals($expectedData, $this->paymentTermManager->getDeleteMessageText($paymentTerm));
//    }
//
//    /**
//     * @return array
//     */
//    public function deleteTextDataProvider()
//    {
//        return [
//            'two links in text' => [
//                'account' => new Account(),
//                'accountGroup' => new AccountGroup(),
//                'expectedData' => static::DEFAULT_DELETE_MESSAGE_TEXT . static::ADDITIONAL_TEXT_WITH_TWO_LINKS
//            ],
//            'account links only' => [
//                'account' => new Account(),
//                'accountGroup' => null,
//                'expectedData' => static::DEFAULT_DELETE_MESSAGE_TEXT . static::ADDITIONAL_TEXT_FOR_ACCOUNT_ONLY
//            ],
//            'account group links only' => [
//                'account' => null,
//                'accountGroup' => new AccountGroup(),
//                'expectedData' => static::DEFAULT_DELETE_MESSAGE_TEXT . static::ADDITIONAL_TEXT_FOR_ACCOUNT_GROUP_ONLY
//            ],
//            'default text only' => [
//                'account' => null,
//                'accountGroup' => null,
//                'expectedData' => static::DEFAULT_DELETE_MESSAGE_TEXT
//            ],
//        ];
//    }
//}
