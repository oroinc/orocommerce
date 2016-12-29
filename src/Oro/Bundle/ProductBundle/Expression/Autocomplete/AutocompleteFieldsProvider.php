<?php

namespace Oro\Bundle\ProductBundle\Expression\Autocomplete;

use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\FieldsProviderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class AutocompleteFieldsProvider
{
    const ROOT_ENTITIES_KEY = 'root_entities';
    const FIELDS_DATA_KEY = 'fields_data';

    const TYPE_RELATION = 'relation';
    const TYPE_INTEGER = 'integer';
    const TYPE_STRING = 'string';
    const TYPE_FLOAT = 'float';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';
    const TYPE_ENUM = 'enum';

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

    /**
     * @param ExpressionParser $expressionParser
     * @param FieldsProviderInterface $fieldsProvider
     * @param TranslatorInterface $translator
     */
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
     * @param string $className
     * @param string $fieldName
     * @param array $information
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
    public function getAutocompleteData($numericalOnly = false, $withRelations = true)
    {
        $data = [
            self::ROOT_ENTITIES_KEY => $this->getRootEntities(),
            self::FIELDS_DATA_KEY => $this->translateLabels(
                array_merge_recursive(
                    $this->getFieldsData($numericalOnly, $withRelations),
                    $this->specialFieldsInformation
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
     * @param bool $numericalOnly
     * @param bool $withRelations
     * @return array
     */
    protected function getFieldsData($numericalOnly, $withRelations)
    {
        $result = [];
        foreach ($this->expressionParser->getNamesMapping() as $rootEntityClassName) {
            $this->fillFields($result, $rootEntityClassName, $numericalOnly, $withRelations);
        }

        return $result;
    }

    /**
     * @param array $result
     * @param string $className
     * @param bool $numericalOnly
     * @param bool $withRelations
     */
    protected function fillFields(array &$result, $className, $numericalOnly, $withRelations)
    {
        if (!array_key_exists($className, $result)) {
            $fields = $this->fieldsProvider->getDetailedFieldsInformation($className, $numericalOnly, $withRelations);
            foreach ($fields as $fieldName => $fieldInfo) {
                $type = $this->getMappedType($fieldInfo['type']);
                if (!$type) {
                    continue;
                }
                $result[$className][$fieldName] = [
                    'label' => $fieldInfo['label'],
                    'type' => $type
                ];
                if ($type === self::TYPE_RELATION) {
                    $relatedEntityName = $fieldInfo['related_entity_name'];
                    $result[$className][$fieldName]['relation_alias'] = $relatedEntityName;
                    $this->fillFields($result, $relatedEntityName, $numericalOnly, $withRelations);
                }
            }
        }
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
                    $item['label'] = $this->translator->trans($item['label']);

                    return $item;
                },
                $fields
            );
        }

        return $data;
    }
}
