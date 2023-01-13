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
    /** @var \PHPUnit\Framework\MockObject\MockObject|VisibilityPostSetDataListener */
    private $visibilityPostSetDataListener;

    /** @var \PHPUnit\Framework\MockObject\MockObject|VisibilityChoicesProvider */
    private $visibilityChoicesProvider;

    /** @var EntityVisibilityType */
    private $formType;

    protected function setUp(): void
    {
        $this->visibilityPostSetDataListener = $this->createMock(VisibilityPostSetDataListener::class);
        $this->visibilityChoicesProvider = $this->createMock(VisibilityChoicesProvider::class);

        $this->formType = new EntityVisibilityType(
            $this->visibilityPostSetDataListener,
            $this->visibilityChoicesProvider
        );
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $validator->expects($this->any())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());
        $validator->expects($this->any())
            ->method('getMetadataFor')
            ->willReturn($metadata);

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
