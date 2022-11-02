<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductSegmentContentWidgetSettingsType;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentChoiceType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class ProductSegmentContentWidgetSettingsTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    protected function setUp(): void
    {
        $this->aclHelper = $this->createMock(AclHelper::class);
        $repository = $this->createMock(SegmentRepository::class);
        $repository->expects($this->any())
            ->method('findByEntity')
            ->with($this->aclHelper, Product::class)
            ->willReturn(['Sample Segment' => 42]);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(Segment::class)
            ->willReturn($repository);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Segment::class)
            ->willReturn($manager);

        parent::setUp();
    }

    public function testSubmit(): void
    {
        $form = $this->factory->create(ProductSegmentContentWidgetSettingsType::class);

        $this->assertEquals(
            [
                'maximum_items' => 4,
                'minimum_items' => 3,
                'use_slider_on_mobile' => false,
                'show_add_button' => true,
            ],
            $form->getData()
        );

        $submittedData = [
            'segment' => 42,
            'maximum_items' => 20,
            'minimum_items' => 15,
            'use_slider_on_mobile' => true,
            'show_add_button' => true,
        ];

        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($submittedData, $form->getData());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    SegmentChoiceType::class => new SegmentChoiceType($this->registry, $this->aclHelper),
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getTypeExtensions(): array
    {
        return array_merge(
            parent::getExtensions(),
            [
                new DataBlockExtension(),
            ]
        );
    }
}
