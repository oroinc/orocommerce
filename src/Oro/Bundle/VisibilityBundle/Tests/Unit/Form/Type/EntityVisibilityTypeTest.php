<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\DataChangesetTypeStub;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\EntityChangesetTypeStub;
use Oro\Bundle\FormBundle\Form\Type\DataChangesetType;
use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityPostSetDataListener;
use Oro\Bundle\VisibilityBundle\Form\Type\EntityVisibilityType;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityChoicesProvider;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntityVisibilityTypeTest extends FormIntegrationTestCase
{
    /**
     * @var EntityVisibilityType
     */
    protected $formType;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|VisibilityPostSetDataListener
     */
    protected $visibilityPostSetDataListener;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|VisibilityChoicesProvider
     */
    protected $visibilityChoicesProvider;

    protected function setUp(): void
    {
        $this->visibilityPostSetDataListener = $this->getMockBuilder(
            VisibilityPostSetDataListener::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->visibilityChoicesProvider = $this
            ->getMockBuilder(VisibilityChoicesProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new EntityVisibilityType(
            $this->visibilityPostSetDataListener,
            $this->visibilityChoicesProvider
        );
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $validator->method('validate')->willReturn(new ConstraintViolationList());
        $validator->method('getMetadataFor')->willReturn($metadata);

        return [
            new PreloadedExtension(
                [
                    EntityVisibilityType::class => $this->formType,
                    DataChangesetType::class => new DataChangesetTypeStub(),
                    EntityChangesetType::class => new EntityChangesetTypeStub(),
                ],
                []
            ),
            new ValidatorExtension($validator),
        ];
    }

    public function testBuildForm()
    {
        $this->visibilityChoicesProvider->expects($this->once())
            ->method('getFormattedChoices')
            ->willReturn([
                'Visible' => 'visible',
                'Hidden' => 'hidden',
            ]);

        $options = [
            'allClass' => ProductVisibility::class,
            'customerGroupClass' => CustomerGroupProductVisibility::class,
            'customerClass' => CustomerProductVisibility::class,
        ];

        $form = $this->factory->create(EntityVisibilityType::class, [], $options);

        $customerGroupData = '{"1":{"visibility":"hidden"},"2":{"visibility":"hidden"}}';
        $customerData = '{"1":{"visibility":"customer_group"},"2":{"visibility":"visible"}}';
        $form->submit(
            [
                'all' => 'visible',
                'customerGroup' => $customerGroupData,
                'customer' => $customerData,
            ]
        );

        $this->assertTrue($form->isSynchronized());
        $this->assertSame('visible', $form->get('all')->getData());
        $this->assertSame($customerData, $form->get('customer')->getData());
        $this->assertSame($customerGroupData, $form->get('customerGroup')->getData());
    }
}
