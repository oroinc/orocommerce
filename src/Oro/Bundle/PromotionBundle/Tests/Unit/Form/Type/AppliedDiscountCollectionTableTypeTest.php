<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\OrderBundle\Form\Type\OrderCollectionTableType;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedDiscountCollectionTableType;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedDiscountRowType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;

class AppliedDiscountCollectionTableTypeTest extends FormIntegrationTestCase
{
    const EXISTING_PROMOTION_ID = 3;
    const NOT_EXISTING_PROMOTION_ID = 1;

    use EntityTrait;

    /**
     * @var AppliedDiscountCollectionTableType
     */
    private $formType;

    protected function setUp()
    {
        parent::setUp();
        $this->formType = new AppliedDiscountCollectionTableType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $existingPromotion = $this->getEntity(Promotion::class, ['id' => self::EXISTING_PROMOTION_ID]);
        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects($this->any())
            ->method('find')
            ->willReturnMap([
                [self::EXISTING_PROMOTION_ID, null, null, $existingPromotion]
            ]);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [Promotion::class, $repository]
            ]);

        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [Promotion::class, $entityManager]
            ]);

        return [
            new PreloadedExtension(
                [
                    AppliedDiscountRowType::NAME => new AppliedDiscountRowType($registry),
                ],
                []
            ),
        ];
    }

    public function testSubmitEnsureSameEnabledStatus()
    {
        /** @var Promotion $existingPromotion */
        $existingPromotion = $this->getEntity(Promotion::class, ['id' => self::EXISTING_PROMOTION_ID]);

        $form = $this->factory->create($this->formType, [
            $firstDiscount = $this->getEntity(AppliedDiscount::class, ['promotion' => $existingPromotion, 'id' => 1]),
            $secondDiscount = $this->getEntity(AppliedDiscount::class, ['promotion' => $existingPromotion, 'id' => 2])
        ]);

        $requestData = [
            0 => [
                'promotion' => self::EXISTING_PROMOTION_ID,
                'enabled' => true
            ],
            1 => [
                'promotion' => self::EXISTING_PROMOTION_ID,
                'enabled' => false
            ]
        ];

        $form->submit($requestData);

        $this->assertEquals([$firstDiscount, $secondDiscount], $form->getData());
    }

    public function testSubmitEnsureNewAppliedDiscountWithoutPromotionAreRemoved()
    {
        $form = $this->factory->create($this->formType);

        $requestData = [
            0 => [
                'promotion' => self::NOT_EXISTING_PROMOTION_ID,
                'enabled' => true
            ],
            1 => [
                'promotion' => self::EXISTING_PROMOTION_ID,
                'enabled' => false
            ]
        ];

        $form->submit($requestData);

        /** @var Promotion $existingPromotion */
        $existingPromotion = $this->getEntity(Promotion::class, ['id' => self::EXISTING_PROMOTION_ID]);
        $appliedDiscount = (new AppliedDiscount())->setPromotion($existingPromotion)->setEnabled(false);

        $this->assertEquals([1 => $appliedDiscount], $form->getData());
    }

    public function testSubmitWhenEmpty()
    {
        $form = $this->factory->create($this->formType);

        $form->submit([]);

        $this->assertEquals([], $form->getData());
    }

    public function testGetParent()
    {
        $this->assertEquals(OrderCollectionTableType::class, $this->formType->getParent());
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create($this->formType);

        $this->assertArraySubset([
            'template_name' => 'OroPromotionBundle:AppliedDiscount:applied_discounts_table.html.twig',
            'page_component' => 'oroui/js/app/components/view-component',
            'page_component_options' => ['view' => 'oropromotion/js/app/views/promotions-view'],
            'attr' => ['class' => 'oro-promotions-collection'],
            'entry_type' => AppliedDiscountRowType::class,
        ], $form->getConfig()->getOptions());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_promotion_applied_discount_collection_table', $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_promotion_applied_discount_collection_table', $this->formType->getBlockPrefix());
    }
}
