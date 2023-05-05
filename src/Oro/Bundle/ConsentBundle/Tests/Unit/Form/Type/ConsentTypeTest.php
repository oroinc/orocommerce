<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsentTypeTest extends FormIntegrationTestCase
{
    /** @var WebCatalogProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $webCatalogProvider;

    /** @var ConsentType */
    private $formType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->webCatalogProvider = $this->createMock(WebCatalogProvider::class);

        $this->formType = new ConsentType(
            $this->webCatalogProvider,
            $this->createMock(FormFactory::class)
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                ],
                [FormType::class => []]
            )
        ];
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->exactly(3))
            ->method('add')
            ->withConsecutive(
                ['names', LocalizedFallbackValueCollectionType::class],
                ['mandatory', ChoiceType::class],
                ['declinedNotification', CheckboxType::class]
            )
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA);

        $this->formType->buildForm($builder, []);
    }

    /**
     * @dataProvider preSetDataProvider
     */
    public function testPreSetData(
        bool $isWebCatalogDefault,
        bool $contentNodeWebCatalog,
        int $expectedFieldsCount,
        bool $isNewConsent
    ) {
        $contentNode = $this->createMock(ContentNode::class);
        $consent = $this->createMock(Consent::class);
        $event = $this->createMock(FormEvent::class);
        $form = $this->createMock(FormInterface::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($consent);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        if ($isNewConsent) {
            $consent->expects($this->once())
                ->method('getId')
                ->willReturn(null);
            $consent->expects($this->once())
                ->method('setDeclinedNotification');
        } else {
            $consent->expects($this->once())
                ->method('getId')
                ->willReturn(random_int(1, 100));
            $consent->expects($this->never())
                ->method('setDeclinedNotification');
        }

        $webCatalog = $this->createMock(WebCatalog::class);

        if ($contentNodeWebCatalog) {
            $consent->expects($this->once())
                ->method('getContentNode')
                ->willReturn($contentNode);
            $contentNode->expects($this->once())
                ->method('getWebCatalog')
                ->willReturn($webCatalog);
        }

        if ($isWebCatalogDefault) {
            $this->webCatalogProvider->expects($this->any())
                ->method('getWebCatalog')
                ->willReturn($webCatalog);
        }

        $form->expects($this->exactly($expectedFieldsCount))
            ->method('add');

        $this->formType->preSetData($event);
    }

    public function preSetDataProvider(): array
    {
        return [
            'web catalog found' => [
                'webcatalog_default' => true,
                'webcatalog_in_content_node' => true,
                'added_fields_count' => 2,
                'is_new_consent' => true
            ],
            'web catalog already setted' => [
                'webcatalog_default' => true,
                'webcatalog_in_content_node' => true,
                'added_fields_count' => 2,
                'is_new_consent' => false
            ],
            'web catalog not found' => [
                'webcatalog_default' => false,
                'webcatalog_in_content_node' => false,
                'added_fields_count' => 2,
                'is_new_consent' => true
            ]
        ];
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => Consent::class]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_consent', $this->formType->getBlockPrefix());
    }
}
