<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormEvent;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use OroB2B\Bundle\PricingBundle\Form\Extension\AccountGroupFormExtension;
use OroB2B\Bundle\PricingBundle\Form\Type\AccountGroupWebsiteScopedPriceListsType;

class AccountGroupFormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var  Registry|\PHPUnit_Framework_MockObject_MockObject */
    private $registry;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
    }
    /**
     * @return AccountGroupFormExtension
     */
    protected function getExtension()
    {
        return new AccountGroupFormExtension($this->registry);
    }

    public function testGetExtendedType()
    {
        $this->assertInternalType('string', $this->getExtension()->getExtendedType());
        $this->assertEquals('orob2b_account_group_type', $this->getExtension()->getExtendedType());
    }

    public function testBuildForm()
    {
        /** @var FormBuilder|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                'priceListsByWebsites',
                AccountGroupWebsiteScopedPriceListsType::NAME
            )
            ->willReturn($builder);
        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'fallback',
                'choice',
                [
                    'label' => 'orob2b.pricing.fallback.label',
                    'mapped' => false,
                    'choices' => [
                        PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY =>
                            'orob2b.pricing.fallback.current_account_group_only.label',
                        PriceListAccountGroupFallback::WEBSITE =>
                            'orob2b.pricing.fallback.website.label',
                    ],
                ]
            );
        $this->getExtension()->buildForm($builder, []);
    }

    public function testOnPostSetDataWithoutData()
    {
        $form = $this->getForm();
        $form->expects($this->never())->method('get');
        $this->getExtension()->onPostSetData(new FormEvent($form, null));
    }

    public function testOnPostSetDataWithoutId()
    {
        $form = $this->getForm();
        $form->expects($this->never())->method('get');
        $this->getExtension()->onPostSetData(new FormEvent($form, new AccountGroup()));
    }

    /**
     * @dataProvider onPostSetDataDataProvider
     *
     * @param PriceListAccountGroupFallback|null $fallbackEntity
     * @param integer $expectedFallbackValue
     */
    public function testOnPostSetData($fallbackEntity, $expectedFallbackValue)
    {
        $fallbackField = $this->getForm();
        $this->setRepositoryFindByExpectations($fallbackEntity);
        $fallbackField->expects($this->once())->method('setData')->with($expectedFallbackValue);
        $form = $this->getForm();
        $form->expects($this->once())->method('get')->with('fallback')->willReturn($fallbackField);
        $accountGroup = $this->getAccountGroup();
        $this->getExtension()->onPostSetData(new FormEvent($form, $accountGroup));
    }

    public function onPostSetDataDataProvider()
    {
        return [
            'notExistingFallback' => [
                'fallbackEntity' => null,
                'expectedFallbackValue' => PriceListAccountGroupFallback::WEBSITE,
            ],
            'existingFallback' => [
                'fallbackEntity' => new PriceListAccountGroupFallback(),
                'expectedFallbackValue' => PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY,
            ],
            'existingDefaultFallback' => [
                'fallbackEntity' => (new PriceListAccountGroupFallback())
                    ->setFallback(PriceListAccountGroupFallback::WEBSITE),
                'expectedFallbackValue' => PriceListAccountGroupFallback::WEBSITE,
            ],
        ];
    }

    public function testOnPostSubmitWithInvalidForm()
    {
        $form = $this->getForm();
        $form->expects($this->once())->method('isValid')->willReturn(false);
        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')->disableOriginalConstructor()->getMock();
        $event->expects($this->once())->method('getForm')->willReturn($form);
        $event->expects($this->never())->method('getData');
        $this->getExtension()->onPostSubmit($event);
    }

    public function testOnPostSubmitWithoutData()
    {
        $form = $this->getForm();
        $form->expects($this->never())->method('get');
        $this->getExtension()->onPostSubmit(new FormEvent($form, null));
    }

    public function testOnPostSubmitWithoutId()
    {
        $form = $this->getForm();
        $form->expects($this->never())->method('get');
        $this->getExtension()->onPostSubmit(new FormEvent($form, new AccountGroup()));
    }

    public function testOnPostSubmitWithExistingFallback()
    {
        $fallbackField = $this->getForm();
        $fallbackValue = PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY;
        $fallbackField->expects($this->once())->method('getData')->willReturn($fallbackValue);
        /** @var  PriceListAccountGroupFallback|\PHPUnit_Framework_MockObject_MockObject $fallbackEntity */
        $fallbackEntity = $this->getMock('OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback');
        $accountGroup = $this->getAccountGroup();
        $fallbackEntity->expects($this->once())->method('setAccountGroup')->with($accountGroup);
        $fallbackEntity->expects($this->once())->method('setFallback')->with($fallbackValue);
        $this->setRepositoryFindByExpectations($fallbackEntity);
        $form = $this->getForm();
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $form->expects($this->once())->method('get')->with('fallback')->willReturn($fallbackField);
        $this->getExtension()->onPostSubmit(new FormEvent($form, $accountGroup));
    }

    public function testOnPostSubmitWithoutExistingFallback()
    {
        $fallbackField = $this->getForm();
        $fallbackValue = PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY;
        $fallbackField->expects($this->once())
            ->method('getData')
            ->willReturn($fallbackValue);
        $accountGroup = $this->getAccountGroup();
        $this->setRepositoryFindByExpectations(null, true);
        $form = $this->getForm();
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $form->expects($this->once())->method('get')->with('fallback')->willReturn($fallbackField);
        $this->getExtension()->onPostSubmit(new FormEvent($form, $accountGroup));
    }

    /**
     * @param null|PriceListAccountGroupFallback $fallback
     * @param bool $persist
     */
    protected function setRepositoryFindByExpectations($fallback, $persist = false)
    {
        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();
        $repo->expects($this->once())->method('findOneBy')->willReturn($fallback);
        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        if ($persist) {
            $em->expects($this->once())->method('persist');
        } else {
            $em->expects($this->never())->method('persist');
        }
        $em->expects($this->once())->method('getRepository')->willReturn($repo);
        $this->registry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroB2BPricingBundle:PriceListAccountGroupFallback')
            ->willReturn($em);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Form
     */
    protected function getForm()
    {
        return $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
    }

    /**
     * @return AccountGroup
     */
    protected function getAccountGroup()
    {
        $entity = new AccountGroup();
        $reflectionClass = new \ReflectionClass('OroB2B\Bundle\AccountBundle\Entity\AccountGroup');
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, 1);

        return $entity;
    }
}
