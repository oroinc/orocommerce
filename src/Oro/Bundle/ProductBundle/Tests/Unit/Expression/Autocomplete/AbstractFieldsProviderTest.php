<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Expression\Autocomplete;

use Oro\Bundle\ProductBundle\Expression\FieldsProvider;
use Oro\Component\Expression\ExpressionParser;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractFieldsProviderTest extends \PHPUnit\Framework\TestCase
{
    const CLASS_NAME = 'className';
    const IS_RELATION = 'isRelation';
    const FIELDS = 'fields';
    const NUMERIC_TYPES = ['integer', 'float'];
    const RELATION_TYPES = ['ref-one'];

    /**
     * @var ExpressionParser|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $expressionParser;

    /**
     * @var FieldsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $fieldsProvider;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    protected function setUp()
    {
        $this->expressionParser = $this->getMockBuilder(ExpressionParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fieldsProvider = $this->createMock(FieldsProvider::class);
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
        $this->fieldsProvider->expects($this->any())
            ->method('getSupportedNumericTypes')
            ->willReturn(self::NUMERIC_TYPES);
        $this->fieldsProvider->expects($this->any())
            ->method('getSupportedRelationTypes')
            ->willReturn(self::RELATION_TYPES);
    }
}
