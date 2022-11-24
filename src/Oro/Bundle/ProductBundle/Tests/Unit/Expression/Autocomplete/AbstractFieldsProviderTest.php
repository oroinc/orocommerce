<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Expression\Autocomplete;

use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\FieldsProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractFieldsProviderTest extends \PHPUnit\Framework\TestCase
{
    protected const CLASS_NAME = 'className';
    protected const IS_RELATION = 'isRelation';
    protected const FIELDS = 'fields';

    /** @var ExpressionParser|\PHPUnit\Framework\MockObject\MockObject */
    protected $expressionParser;

    /** @var FieldsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $fieldsProvider;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    protected function setUp(): void
    {
        $this->expressionParser = $this->createMock(ExpressionParser::class);
        $this->fieldsProvider = $this->createMock(FieldsProviderInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
    }

    protected function getMap(array $fieldsData, bool $numericalOnly, bool $withRelations): array
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

    protected function configureDependencies(array $fieldsData, bool $numericalOnly, bool $withRelations): void
    {
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($str) {
                return $str . ' TRANS';
            });
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
