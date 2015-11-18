<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use OroB2B\Bundle\PricingBundle\Form\Type\AccountWebsiteScopedPriceListsType;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class AccountWebsiteScopedPriceListsTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var AccountWebsiteScopedPriceListsType */
    protected $formType;

    /** @var PriceListToAccountRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var Account */
    protected $targetEntity;

    /** @var Website */
    protected $website;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BPricingBundle:PriceListToAccount')
            ->willReturn($this->repository);

        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder('\Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroB2BPricingBundle:PriceListToAccount')
            ->willReturn($this->em);

        $this->targetEntity = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 123]);
        $this->website = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', ['id' => 42]);

        $this->formType = new AccountWebsiteScopedPriceListsType($registry);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->em, $this->repository, $this->targetEntity, $this->website, $this->formType);
    }

    public function testGetName()
    {
        $this->assertEquals(AccountWebsiteScopedPriceListsType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(WebsiteScopedDataType::NAME, $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->will($this->returnSelf());

        $this->formType->configureOptions($resolver);
    }

    public function testBuildForm()
    {
        /** @var FormBuilder|\PHPUnit_Framework_MockObject_MockObject|OptionsResolver $builder */
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->at(0))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA)
            ->will($this->returnSelf());

        $builder->expects($this->at(1))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT)
            ->will($this->returnSelf());

        $this->formType->buildForm($builder, []);
    }

    public function testOnPreSetData()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', ['id' => 1]);

        /** @var PriceListToAccount $priceListToTargetEntity */
        $priceListToTargetEntity = new PriceListToAccount();

        $priceListToTargetEntity->setPriceList($priceList);
        $priceListToTargetEntity->setPriority(100);

        $this->repository->expects($this->any())
            ->method('getPriceLists')
            ->with($this->targetEntity, $this->website)
            ->willReturn([
                $priceListToTargetEntity
            ]);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $parentForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $priceListByWebsitesForm = $this->getMock('Symfony\Component\Form\FormInterface');

        $formConfig = $this->getMock('Symfony\Component\Form\FormConfigInterface');

        $formConfig->expects($this->once())
            ->method('getOption')
            ->with('website')
            ->willReturn($this->website);

        $priceListByWebsitesForm->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $parentForm->expects($this->once())
            ->method('getData')
            ->willReturn($this->targetEntity);

        $priceListsByWebsitesForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $priceListsByWebsitesForm->expects($this->once())
            ->method('all')
            ->willReturn([$priceListByWebsitesForm]);


        $parentForm->expects($this->once())
            ->method('get')
            ->with('priceListsByWebsites')
            ->willReturn($priceListsByWebsitesForm);

        $form->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm);

        /** @var $event FormEvent|\PHPUnit_Framework_MockObject_MockObject */
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $event->expects($this->once())
            ->method('setData')
            ->with([
                '42' => [
                    [
                        'priceList' => $priceList,
                        'priority' => 100
                    ]
                ]
            ]);

        $this->formType->onPreSetData($event);
    }

    public function testSkipOnPreSetData()
    {
        $event = $this->getSkippedEvent();

        $this->formType->onPreSetData($event);
    }

    /**
     * @dataProvider onPostSubmitDataProvider
     * @param array $submittedData
     * @param array $actualData
     */
    public function testOnPostSubmit(array $submittedData, array $actualData)
    {
        $actualPriceLists = [];
        foreach ($actualData as $item) {
            $priceListToTargetEntity = new PriceListToAccount();
            $priceListToTargetEntity->setPriceList($item['priceList']);
            $priceListToTargetEntity->setPriority($item['priority']);

            $actualPriceLists[] = $priceListToTargetEntity;
        }

        $this->repository->expects($this->any())
            ->method('getPriceLists')
            ->with($this->targetEntity, $this->website)
            ->willReturn($actualPriceLists);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $parentForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $priceListByWebsitesForm = $this->getMock('Symfony\Component\Form\FormInterface');

        $formConfig = $this->getMock('Symfony\Component\Form\FormConfigInterface');

        $formConfig->expects($this->once())
            ->method('getOption')
            ->with('website')
            ->willReturn($this->website);

        $priceListByWebsitesForm->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $parentForm->expects($this->once())
            ->method('getData')
            ->willReturn($this->targetEntity);

        $priceListByWebsitesForm->expects($this->once())
            ->method('getData')
            ->willReturn($submittedData);

        $priceListWithPriorityForm = $this->getMock('Symfony\Component\Form\FormConfigInterface');

        $priceListWithPriorityForm->expects($this->any())
            ->method('getData')
            ->willReturnOnConsecutiveCalls($submittedData[0]);

        $priceListByWebsitesForm->expects($this->once())
            ->method('all')
            ->willReturnOnConsecutiveCalls([$priceListWithPriorityForm]);

        $priceListsByWebsitesForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $priceListsByWebsitesForm->expects($this->any())
            ->method('all')
            ->willReturn([$priceListByWebsitesForm]);

        $parentForm->expects($this->once())
            ->method('get')
            ->with('priceListsByWebsites')
            ->willReturn($priceListsByWebsitesForm);

        $form->expects($this->any())
            ->method('getParent')
            ->willReturn($parentForm);

        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        /** @var $event FormEvent|\PHPUnit_Framework_MockObject_MockObject */
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->any())
            ->method('getForm')
            ->willReturn($form);

        $submittedPriceLists = array_map(
            function ($submittedItem) {
                return $submittedItem['priceList'];
            },
            $submittedData
        );

        /** @var BasePriceListRelation[] $actualPriceLists */
        foreach ($actualPriceLists as $actualPriceList) {
            if (!in_array($actualPriceList->getPriceList(), $submittedPriceLists)) {
                $this->em->remove($actualPriceList);
            }
        }

        $this->formType->onPostSubmit($event);
    }

    /**
     * @return array
     */
    public function onPostSubmitDataProvider()
    {
        /** @var PriceList $priceList1 */
        $priceList1 = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', ['id' => 1]);
        /** @var PriceList $priceList2 */
        $priceList2 = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', ['id' => 2]);

        return [
            'with removed' => [
                'submittedData' => [
                    [
                        'priceList' => $priceList1,
                        'priority' => 100
                    ]
                ],
                'actualData' => [
                    [
                        'priceList' => $priceList1,
                        'priority' => 100
                    ],
                    [
                        'priceList' => $priceList2,
                        'priority' => 200
                    ],
                ]
            ],
            'with updated' => [
                'submittedData' => [
                    [
                        'priceList' => $priceList1,
                        'priority' => 100
                    ]
                ],
                'actualData' => [
                    [
                        'priceList' => $priceList1,
                        'priority' => 3
                    ]
                ]
            ],
            'with new' => [
                'submittedData' => [
                    [
                        'priceList' => $priceList1,
                        'priority' => 100
                    ]
                ],
                'actualData' => []
            ],
        ];
    }

    public function testSkipOnPostSubmitWithoutTargetEntity()
    {
        $event = $this->getSkippedEvent();

        $this->formType->onPostSubmit($event);
    }

    public function testSkipOnPostSubmitInvalidForm()
    {
        $event = $this->getSkippedEvent(false, $this->targetEntity);


        $this->formType->onPostSubmit($event);
    }

    /**
     * @param bool $isValidForm
     * @param Account $targetEntity
     * @return \PHPUnit_Framework_MockObject_MockObject|FormEvent
     */
    protected function getSkippedEvent($isValidForm = false, Account $targetEntity = null)
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $parentForm = $this->getMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm);

        if ($targetEntity && $isValidForm) {
            $form->expects($this->once())
                ->method('isValid')
                ->willReturn($isValidForm);
        }

        $parentForm->expects($this->once())
            ->method('getData')
            ->willReturn($targetEntity);

        /** @var $event FormEvent|\PHPUnit_Framework_MockObject_MockObject */
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->any())
            ->method('getForm')
            ->willReturn($form);

        $parentForm->expects($this->never())
            ->method('get')
            ->with('priceListsByWebsites');

        return $event;
    }
}
