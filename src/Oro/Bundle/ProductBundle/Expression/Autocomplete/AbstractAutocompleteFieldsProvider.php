<?php

namespace Oro\Bundle\ProductBundle\Expression\Autocomplete;

use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\FieldsProviderInterface;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides base functionality for autocomplete fields.
 */
abstract class AbstractAutocompleteFieldsProvider implements AutocompleteFieldsProviderInterface
{
    /**
     * @var array
     */
    protected static $typesMap = [
        'string' => self::TYPE_STRING,
        'text' => self::TYPE_STRING,
        'boolean' => self::TYPE_BOOLEAN,
        'enum' => self::TYPE_ENUM,
        'integer' => self::TYPE_INTEGER,
        'float' => self::TYPE_FLOAT,
        'money' => self::TYPE_FLOAT,
        'double' => self::TYPE_FLOAT,
        'datetime' => self::TYPE_DATETIME,
        'date' => self::TYPE_DATE,
        'manyToMany' => self::TYPE_RELATION,
        'oneToMany' => self::TYPE_RELATION,
        'manyToOne' => self::TYPE_RELATION,
        'ref-many' => self::TYPE_RELATION,
        'ref-one' => self::TYPE_RELATION
    ];

    /**
     * @var ExpressionParser
     */
    protected $expressionParser;

    /**
     * @var FieldsProviderInterface
     */
    protected $fieldsProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $specialFieldsInformation = [];

    public function __construct(
        ExpressionParser $expressionParser,
        FieldsProviderInterface $fieldsProvider,
        TranslatorInterface $translator
    ) {
        $this->expressionParser = $expressionParser;
        $this->fieldsProvider = $fieldsProvider;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function addSpecialFieldInformation($className, $fieldName, array $information)
    {
        $this->specialFieldsInformation[$className][$fieldName] = $information;
    }

    /**
     * @param bool $numericalOnly
     * @param bool $withRelations
     * @return array
     */
    abstract protected function getFieldsData($numericalOnly, $withRelations);

    /**
     * {@inheritdoc}
     */
    public function getAutocompleteData($numericalOnly = false, $withRelations = true)
    {
        $data = [
            self::ROOT_ENTITIES_KEY => $this->getRootEntities(),
            self::FIELDS_DATA_KEY => $this->translateLabels(
                ArrayUtil::arrayMergeRecursiveDistinct(
                    $this->getFieldsData($numericalOnly, $withRelations),
                    ($numericalOnly ? [] : $this->specialFieldsInformation)
                )
            )
        ];

        return $data;
    }

    /**
     * @return array
     */
    protected function getRootEntities()
    {
        return $this->expressionParser->getReverseNameMapping();
    }

    /**
     * @param string $type
     * @return string|null
     */
    protected function getMappedType($type)
    {
        if (array_key_exists($type, self::$typesMap)) {
            return self::$typesMap[$type];
        }

        return null;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function translateLabels(array $data)
    {
        foreach ($data as &$fields) {
            $fields = array_map(
                function (array $item) {
                    $item['label'] = isset($item['label']) ? $this->translator->trans((string) $item['label']) : '';

                    return $item;
                },
                $fields
            );
        }

        return $data;
    }

    protected function removeEmptyRelations(array &$result)
    {
        $hasChanges = true;
        while ($hasChanges) {
            $hasChanges = false;
            $result = array_filter(
                $result,
                function ($fields) use (&$hasChanges) {
                    $requireRemove = count($fields) === 0;
                    $hasChanges = $hasChanges || $requireRemove;

                    return !$requireRemove;
                }
            );
            foreach ($result as $className => &$fields) {
                $fields = array_filter(
                    $fields,
                    function ($fieldInfo) use ($result, $className, &$hasChanges) {
                        $requireRemove = $fieldInfo['type'] === self::TYPE_RELATION
                            && (!array_key_exists($fieldInfo['relation_alias'], $result)
                                || $fieldInfo['relation_alias'] === $className);
                        $hasChanges = $hasChanges || $requireRemove;

                        return !$requireRemove;
                    }
                );
            }
        }
    }
}
