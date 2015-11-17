<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\PricingBundle\Form\Type\AccountWebsiteScopedPriceListsType;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;

class AccountWebsiteScopedPriceListsTypeTest extends FormIntegrationTestCase
{
    const WEBSITE_ID = 42;

    /**
     * @var AccountWebsiteScopedPriceListsType
     */
    protected $formType;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    protected function getExtensions()
    {
        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $website = $this->createWebsite(self::WEBSITE_ID);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getReference')
            ->with('TestWebsiteClass', self::WEBSITE_ID)
            ->willReturn($website);

        $repository = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->any())
            ->method('getAllWebsites')
            ->willReturn([$website]);

        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $this->registry = $this->getMockBuilder('\Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('TestWebsiteClass')
            ->willReturn($em);

        $websiteScopedDataType = new WebsiteScopedDataType($this->registry);

        return [
            new PreloadedExtension(
                [
                    WebsiteScopedDataType::NAME => $websiteScopedDataType,
                ],
                []
            )
        ];
    }

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new AccountWebsiteScopedPriceListsType($this->registry);
    }

    /**
     * @dataProvider submitDataProvider
     * @param array $defaultData
     * @param array $options
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit($defaultData, array $options, array $submittedData, array $expectedData)
    {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [
                'defaultData'   => [],
                'options' => [
                    'preloaded_websites' => [],
                    'type' => new PriceListCollectionType()
                ],
                'submittedData' => [
                    self::WEBSITE_ID => [],
                ],
                'expectedData'  => [
                    self::WEBSITE_ID => [],
                ],
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(AccountWebsiteScopedPriceListsType::NAME, $this->formType->getName());
    }

    /**
     * @param int $id
     * @return Website
     */
    protected function createWebsite($id)
    {
        $website = new Website();
        $idReflection = new \ReflectionProperty($website, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($website, $id);

        return $website;
    }
}
