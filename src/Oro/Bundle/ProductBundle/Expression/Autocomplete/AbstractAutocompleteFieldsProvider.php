<?php

namespace Oro\Bundle\ProductBundle\Expression\Autocomplete;

use Oro\Component\Expression\ExpressionParser;
use Oro\Bundle\ProductBundle\Expression\FieldsProvider;
use Symfony\Component\Translation\TranslatorInterface;

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
        'decimal' => self::TYPE_FLOAT,
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
     * @var FieldsProvider
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

    /**
     * @param ExpressionParser $expressionParser
     * @param FieldsProvider $fieldsProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ExpressionParser $expressionParser,
        FieldsProvider $fieldsProvider,
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
    public function getDataProviderConfig($numericalOnly = false, $withRelations = true)
    {
        $typeRelation = self::TYPE_RELATION;
        $scalarTypes = array_filter(self::$typesMap, function ($type) use ($typeRelation) {
            return $type !== $typeRelation;
        });

        $optionsFilter = [
            'unidirectional' => false,
            'exclude' => false,
        ];
        $include = [];

        if ($numericalOnly) {
            // identifier fields should not be available for math operations
            $optionsFilter['identifier'] = false;
            $include = array_merge($include, array_map(function ($type) {
                return ['type' => $type];
            }, $this->fieldsProvider->getSupportedNumericTypes()));
        } else {
            $include = array_merge($include, array_map(function ($type) {
                return ['type' => $type];
            }, array_keys($scalarTypes)));
        }

        if ($withRelations) {
            $include = array_merge($include, array_map(function ($type) {
                return ['type' => $type];
            }, $this->fieldsProvider->getSupportedRelationTypes()));
        } else {
            $optionsFilter['relation'] = false;
        }

        $dataProviderConfig = [
            'optionsFilter' => $optionsFilter,
        ];

        if (!empty($include)) {
            $dataProviderConfig['include'] = $include;
        }

        $fieldsFilterWhitelist = $this->fieldsProvider->getFieldsWhiteList();
        if (!empty($fieldsFilterWhitelist)) {
            $dataProviderConfig['fieldsFilterWhitelist'] = $fieldsFilterWhitelist;
        }

        $fieldsFilterBlacklist = $this->fieldsProvider->getFieldsBlackList();
        if (!empty($fieldsFilterBlacklist)) {
            $dataProviderConfig['fieldsFilterBlacklist'] = $fieldsFilterBlacklist;
        }

        return $dataProviderConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootEntities()
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
                    if (!empty($item['label'])) {
                        $item['label'] = $this->translator->trans($item['label']);
                    }

                    return $item;
                },
                $fields
            );
        }

        return $data;
    }

    /**
     * @param array $result
     */
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
