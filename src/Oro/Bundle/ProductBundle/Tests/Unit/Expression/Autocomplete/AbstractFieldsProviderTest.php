<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Expression\Autocomplete;

use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\FieldsProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractFieldsProviderTest extends \PHPUnit\Framework\TestCase
{
    const CLASS_NAME = 'className';
    const IS_RELATION = 'isRelation';
    const FIELDS = 'fields';

    /**
     * @var ExpressionParser|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $expressionParser;

    /**
     * @var FieldsProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $fieldsProvider;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    protected function setUp(): void
    {
        $this->expressionParser = $this->getMockBuilder(ExpressionParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fieldsProvider = $this->createMock(FieldsProviderInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
    }

    /**
     * @param array $fieldsData
     * @param bool $numericalOnly
     * @param bool $withRelations
     * @return array
     */
    protected function getMap(array $fieldsData, $numericalOnly, $withRelations)
    {
        $map = [];
        foreach ($fieldsData as $data) {
            $map[] = [
                $data[self::CLASS_NAME],
                $numericalOnly,
                $withRelations && !$data[self::IS_RELATION],
                $data[self::FIELDS]
            ];
        }

        return $map;
    }

    /**
     * @param array $fieldsData
     * @param bool $numericalOnly
     * @param bool $withRelations
     */
    protected function configureDependencies(array $fieldsData, $numericalOnly, $withRelations)
    {
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($str) {
                    return $str . ' TRANS';
                }
            );
        $this->expressionParser->expects($this->any())
            ->method('getReverseNameMapping')
            ->willReturn(['ProductClass' => 'product']);
        $this->expressionParser->expects($this->any())
            ->method('getNamesMapping')
            ->willReturn(['product' => 'ProductClass']);
        $this->fieldsProvider->expects($this->any())
            ->method('getDetailedFieldsInformation')
            ->willReturnMap($this->getMap($fieldsData, $numericalOnly, $withRelations));
    }
}
