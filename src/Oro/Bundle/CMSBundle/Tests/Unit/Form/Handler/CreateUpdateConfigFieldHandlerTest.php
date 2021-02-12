<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\CMSBundle\Form\Handler\CreateUpdateConfigFieldHandler;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Handler\ConfigHelperHandler;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Util\FieldSessionStorage;
use Oro\Component\Testing\Unit\EntityTrait;

class CreateUpdateConfigFieldHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const FIELD_NAME = 'field_name';

    const FIELD_TYPE = 'wysiwyg';

    /**
     * @var ConfigHelperHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configHelperHandler;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var ConfigHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configHelper;

    /**
     * @var FieldSessionStorage|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sessionStorage;

    /**
     * @var CreateUpdateConfigFieldHandler
     */
    private $handler;

    protected function setUp(): void
    {
        $this->configHelperHandler = $this->createMock(ConfigHelperHandler::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->sessionStorage = $this->createMock(FieldSessionStorage::class);

        $this->handler = new CreateUpdateConfigFieldHandler(
            $this->configHelperHandler,
            $this->configManager,
            $this->configHelper,
            $this->sessionStorage
        );
    }

    /**
     * @dataProvider fieldOptionsDataProvider
     */
    public function testCreateFieldModel(
        array $fieldOptions,
        array $styleFieldOptions,
        array $propertiesFieldOptions
    ): void {
        $extendEntityConfig = new Config(new EntityConfigId('extend', \stdClass::class));

        $this->configHelper->expects($this->once())
            ->method('createFieldOptions')
            ->with($extendEntityConfig, self::FIELD_TYPE, [])
            ->willReturn([self::FIELD_TYPE, $fieldOptions]);

        $newFieldModel = $this->getEntity(FieldConfigModel::class);
        $newFieldStyleModel = $this->getEntity(FieldConfigModel::class);
        $newFieldPropertiesModel = $this->getEntity(FieldConfigModel::class);

        $this->configManager->expects($this->any())
            ->method('createConfigFieldModel')
            ->withConsecutive(
                [\stdClass::class, self::FIELD_NAME, self::FIELD_TYPE],
                [\stdClass::class, self::FIELD_NAME . WYSIWYGStyleType::TYPE_SUFFIX, WYSIWYGStyleType::TYPE],
                [\stdClass::class, self::FIELD_NAME . WYSIWYGPropertiesType::TYPE_SUFFIX, WYSIWYGPropertiesType::TYPE]
            )
            ->willReturnOnConsecutiveCalls($newFieldModel, $newFieldStyleModel, $newFieldPropertiesModel);

        $this->configHelper->expects($this->exactly(3))
            ->method('updateFieldConfigs')
            ->withConsecutive(
                [$newFieldModel, $fieldOptions],
                [$newFieldStyleModel, $styleFieldOptions],
                [$newFieldPropertiesModel, $propertiesFieldOptions]
            );

        $this->handler->createFieldModel(self::FIELD_NAME, self::FIELD_TYPE, $extendEntityConfig);
    }

    public function fieldOptionsDataProvider(): array
    {
        return [
            [
                'fieldOptions' => [
                    'attribute' => [
                        'is_attribute' => true,
                    ]
                ],
                'styleFieldOptions' => [
                    'extend' => [
                        'is_serialized' => true
                    ],
                    'attribute' => [
                        'is_attribute' => false,
                        'field_name' => self::FIELD_NAME . WYSIWYGStyleType::TYPE_SUFFIX,
                    ]
                ],
                'propertiesFieldOptions' => [
                    'extend' => [
                        'is_serialized' => true
                    ],
                    'attribute' => [
                        'is_attribute' => false,
                        'field_name' => self::FIELD_NAME . WYSIWYGPropertiesType::TYPE_SUFFIX,
                    ]
                ],
            ],
            [
                'fieldOptions' => [
                    'extend' => [
                        'is_extend' => true,
                        'owner' => ExtendScope::OWNER_CUSTOM,
                        'state' => ExtendScope::STATE_NEW,
                        'is_serialized' => true
                    ],
                    'attribute' => [
                        'is_attribute' => true,
                        'field_name' => self::FIELD_NAME
                    ]
                ],
                'styleFieldOptions' => [
                    'extend' => [
                        'is_extend' => true,
                        'owner' => ExtendScope::OWNER_CUSTOM,
                        'state' => ExtendScope::STATE_NEW,
                        'is_serialized' => true
                    ],
                    'attribute' => [
                        'is_attribute' => false,
                        'field_name' => self::FIELD_NAME . WYSIWYGStyleType::TYPE_SUFFIX,
                    ]
                ],
                'propertiesFieldOptions' => [
                    'extend' => [
                        'is_extend' => true,
                        'owner' => ExtendScope::OWNER_CUSTOM,
                        'state' => ExtendScope::STATE_NEW,
                        'is_serialized' => true
                    ],
                    'attribute' => [
                        'is_attribute' => false,
                        'field_name' => self::FIELD_NAME . WYSIWYGPropertiesType::TYPE_SUFFIX,
                    ]
                ]
            ]
        ];
    }
}
