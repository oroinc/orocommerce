<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedDiscountRowType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class AppliedDiscountRowTypeTest extends FormIntegrationTestCase
{
    const EXISTING_PROMOTION_ID = 3;

    use EntityTrait;

    /**
     * @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    /**
     * @var AppliedDiscountRowType
     */
    private $formType;

    protected function setUp()
    {
        parent::setUp();
        $this->markTestSkipped('Skipped. Fixed\refactored along with related type in BB-11292.');

        $this->repository = $this->createMock(EntityRepository::class);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [Promotion::class, $this->repository]
            ]);

        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [Promotion::class, $entityManager]
            ]);

        $this->formType = new AppliedDiscountRowType($registry);
    }

    /**
     * dataProvider buildViewDataProvider
     * @param null|AppliedDiscount $defaultData
     * @param null|int $expectedPromotion
     * @param bool $expectedEnabled
     */
    public function testBuildView($defaultData, $expectedPromotion, $expectedEnabled)
    {
        $form = $this->factory->create($this->formType, $defaultData);
        $formView = $form->createView();

        $this->assertEquals($expectedPromotion, $formView->children['promotion']->vars['value']);
        $this->assertEquals($expectedEnabled, $formView->children['enabled']->vars['value']);
    }

    /**
     * @return array
     */
    public function buildViewDataProvider()
    {
        return [
            'null data no promotion' => [
                'appliedDiscount' => null,
                'promotion' => null,
                'enabled' => false
            ],
            'source promotion id used if applied discount is saved' => [
                'appliedDiscount' => $this->getEntity(AppliedDiscount::class, ['id' => 1, 'sourcePromotionId' => 5]),
                'promotion' => 5,
                'enabled' => true
            ],
            'promotion used if applied discount is not saved' => [
                'appliedDiscount' => $this->getEntity(AppliedDiscount::class, [
                    'promotion' => $this->getEntity(Promotion::class, ['id' => 7]),
                    'enabled' => false
                ]),
                'promotion' => 7,
                'enabled' => false
            ],
            'promotion is null if applied discount is not saved and no promotion set' => [
                'appliedDiscount' => new AppliedDiscount(),
                'promotion' => null,
                'enabled' => true
            ]
        ];
    }

    /**
     * dataProvider submitDataProvider
     * @param AppliedDiscount|null $defaultData
     * @param array $submittedData
     * @param AppliedDiscount $expectedData
     */
    public function testSubmit($defaultData, array $submittedData, $expectedData)
    {
        $form = $this->factory->create($this->formType, $defaultData);

        $this->repository
            ->expects($this->any())
            ->method('find')
            ->willReturnMap([
                [self::EXISTING_PROMOTION_ID, null, null, $this->getEntity(Promotion::class, ['id' => 3])]
            ]);

        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider(): array
    {
        /** @var AppliedDiscount $appliedDiscount */
        $appliedDiscount = $this->getEntity(AppliedDiscount::class, [
            'id' => 1,
            'promotion' => $this->getEntity(Promotion::class, ['id' => 1]),
            'enabled' => false
        ]);

        /** @var Promotion $promotion */
        $promotion = $this->getEntity(Promotion::class, ['id' => self::EXISTING_PROMOTION_ID]);

        return [
            'promotion is not changeable for existing applied discount' => [
                'defaultData' => $appliedDiscount,
                'submittedData' => [
                    'promotion' => self::EXISTING_PROMOTION_ID,
                    'enabled' => true
                ],
                'expectedData' => $appliedDiscount
            ],
            'promotion and enabled fields are set for new applied discount' => [
                'defaultData' => null,
                'submittedData' => [
                    'promotion' => self::EXISTING_PROMOTION_ID,
                    'enabled' => true
                ],
                'expectedData' => (new AppliedDiscount())->setEnabled(true)->setPromotion($promotion)
            ],
        ];
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create($this->formType);

        $this->assertArraySubset(
            ['data_class' => AppliedDiscount::class],
            $form->getConfig()->getOptions()
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_promotion_applied_discount_row', $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_promotion_applied_discount_row', $this->formType->getBlockPrefix());
    }
}
