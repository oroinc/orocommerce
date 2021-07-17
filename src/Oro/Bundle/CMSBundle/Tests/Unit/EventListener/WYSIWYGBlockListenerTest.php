<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\EventListener;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\CMSBundle\EventListener\WYSIWYGBlockListener;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;
use Oro\Bundle\UIBundle\Twig\UiExtension;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class WYSIWYGBlockListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $environment;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigProvider;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var WYSIWYGBlockListener */
    private $listener;

    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(
                static function ($entity) {
                    return get_class($entity);
                }
            );

        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnCallback(
                static function ($className, $fieldName) {
                    return new Config(
                        new FieldConfigId('entity', $className, $fieldName),
                        ['label' => $fieldName . ' label']
                    );
                }
            );

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                static function ($key) {
                    return $key . ' translated';
                }
            );

        $this->listener = new WYSIWYGBlockListener(
            $this->doctrineHelper,
            $this->entityConfigProvider,
            $this->translator
        );
    }

    /**
     * @dataProvider onBeforeFormRenderDataProvider
     *
     * @param object $entity
     * @param array $configIds
     * @param array $formData
     * @param array $expectedData
     */
    public function testOnBeforeFormRender($entity, array $configIds, array $formData, array $expectedData): void
    {
        $this->entityConfigProvider->expects($this->any())
            ->method('getIds')
            ->willReturn($configIds);

        $event = new BeforeFormRenderEvent(new FormView(), $formData, $this->environment, $entity);

        $this->listener->onBeforeFormRender($event);

        $this->assertEquals($expectedData, $event->getFormData());
    }

    public function onBeforeFormRenderDataProvider(): array
    {
        $formData = [
            ScrollData::DATA_BLOCKS => [
                [
                    'title' => 'Group1Title',
                    'useSubBlockDivider' => true,
                    'subblocks' => [
                        [
                            'data' => [
                                'field1' => 'field template',
                                'otherField' => 'field template',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expectedData = [
            ScrollData::DATA_BLOCKS => [
                [
                    'title' => 'Group1Title',
                    'useSubBlockDivider' => true,
                    'subblocks' => [
                        [
                            'data' => [
                                'otherField' => 'field template'
                            ],
                        ],
                    ],
                ],
                'field1_block_section' => [
                    'title' => 'field1 label translated',
                    'useSubBlockDivider' => true,
                    'subblocks' => [
                        [
                            'data' => [
                                'field1' => 'field template'
                            ],
                        ],
                    ],
                    'priority' => UiExtension::ADDITIONAL_SECTION_PRIORITY - 1,
                ],
            ],
        ];

        return [
            'no changes (no entity)' => [
                'entity' => null,
                'configIds' => [new FieldConfigId('test', \stdClass::class, 'field1', 'enum')],
                'formData' => $formData,
                'expectedData' => $formData,
            ],
            'no changes (no field configs)' => [
                'entity' => new \stdClass(),
                'configIds' => [],
                'formData' => $formData,
                'expectedData' => $formData,
            ],
            'no changes (unsupported field type)' => [
                'entity' => new \stdClass(),
                'configIds' => [new FieldConfigId('test', \stdClass::class, 'field1', 'enum')],
                'formData' => $formData,
                'expectedData' => $formData,
            ],
            'no changes (other field name)' => [
                'entity' => new \stdClass(),
                'configIds' => [new FieldConfigId('test', \stdClass::class, 'field2', WYSIWYGType::TYPE)],
                'formData' => $formData,
                'expectedData' => $formData,
            ],
            'move wysiwyg field to own group' => [
                'entity' => new \stdClass(),
                'configIds' => [new FieldConfigId('test', \stdClass::class, 'field1', WYSIWYGType::TYPE)],
                'formData' => $formData,
                'expectedData' => $expectedData,
            ],
        ];
    }
}
