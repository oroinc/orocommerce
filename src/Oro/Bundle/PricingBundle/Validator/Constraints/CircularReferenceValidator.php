<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Expression\ExpressionParser;
use Oro\Bundle\PricingBundle\Expression\NameNode;
use Oro\Bundle\PricingBundle\Expression\RelationNode;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CircularReferenceValidator extends ConstraintValidator
{
    /**
     * @var ExpressionParser
     */
    protected $expressionParser;

    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var array
     */
    protected $references;

    /**
     * @var string|null
     */
    protected $container = null;

    /**
     * @var PropertyAccess
     */
    protected $accessor;

    /**
     * @var array
     */
    protected static $priceRuleProperties = [
        'rule', 'ruleCondition', 'currencyExpression', 'quantityExpression', 'productUnitExpression'
    ];

    /**
     * @var array
     */
    protected static $priceListProperties = ['productAssignmentRule'];

    /**
     * @param ExpressionParser $expressionParser
     * @param RegistryInterface $doctrine
     */
    public function __construct(
        ExpressionParser $expressionParser,
        RegistryInterface $doctrine
    ) {
        $this->expressionParser = $expressionParser;
        $this->doctrine = $doctrine;

        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param $value
     * @param CircularReference $constraint
     *
     * {@inheritdoc}
     */
    public function validate($object, Constraint $constraint)
    {
        if (empty($object->getId())) {
            return;
        }

        try {
            foreach ($constraint->fields as $field) {
                $expressions = [$this->getFieldValue($object, $field)];

                if ($object instanceof PriceList) {
                    $this->references = [$object->getId()];
                } elseif (($priceListId = $this->getFieldValue($object, 'priceList')->getId()) !== null) {
                    $this->references = [$priceListId];
                } else {
                    break;
                }

                while (true) {
                    $nodes = $this->parseExpression($expressions);
                    $references = $this->findReferences($nodes);

                    if ($this->hasCircularReference($references)) {
                        $this->context->buildViolation($constraint->message)->atPath($field)->addViolation();
                        break;
                    }

                    if (count($references) === 0) {
                        break;
                    }

                    $this->references = array_merge($this->references, $references);
                    $expressions = $this->findExpressions($references);
                }
            }
        } catch (\Exception $ex) {
            $this->context->buildViolation($constraint->invalidNodeMessage)->atPath($field)->addViolation();
        }
    }

    /**
     * @param array $references
     * @return bool
     */
    protected function hasCircularReference($references)
    {
        return (count(array_intersect($this->references, $references)) > 0);
    }

    /**
     * @param array $expressions
     * @return array
     */
    protected function parseExpression($expressions)
    {
        $nodes = [];
        foreach ($expressions as $expression) {
            $rootNode = $this->expressionParser->parse($expression);
            if ($rootNode !== null) {
                $nodes = array_merge($nodes, $rootNode->getNodes());
            }
        }

        return $nodes;
    }

    /**
     * @param array $nodes
     * @return array
     */
    protected function findReferences($nodes)
    {
        $references = [];

        foreach ($nodes as $node) {
            if (!$this->isSupportedNode($node)) {
                continue;
            }
            $containerId = $node->getContainerId();
            if (!empty($containerId)) {
                $references[] = $containerId;
            }
        }
        $references = array_unique($references);
        return $references;
    }

    /**
     * @param $references
     * @return array
     */
    protected function findExpressions($references)
    {
        $expressions = [];

        foreach ($references as $id) {
            $priceList = $this->getPriceList($id);
            $expressions = array_merge(
                $expressions,
                $this->collectExpressions($priceList, self::$priceListProperties)
            );

            $priceRules = $this->getPriceRulesByPriceList($id);

            foreach ($priceRules as $priceRule) {
                $expressions = array_merge(
                    $expressions,
                    $this->collectExpressions($priceRule, self::$priceRuleProperties)
                );
            }
        }
        return $expressions;
    }

    /**
     * @param $entity
     * @param array $properties
     *
     * @return array
     */
    protected function collectExpressions($entity, $properties)
    {
        $expressions = [];
        foreach ($properties as $property) {
            if ((($expression = $this->getFieldValue($entity, $property)) !== null) &&
                (!empty($expression))
            ) {
                $expressions[] = $expression;
            }
        }
        return $expressions;
    }

    /**
     * @param $entity
     * @param string $field
     *
     * @return string|null
     */
    protected function getFieldValue($entity, $field)
    {
        $value = null;
        if ($this->accessor->isReadable($entity, $field)) {
            $value = $this->accessor->getValue($entity, $field);
        }
        return $value;
    }

    /**
     * @param int $id
     * @return PriceList
     */
    protected function getPriceList($id)
    {
        return $this->doctrine
            ->getManagerForClass(PriceList::class)
            ->getRepository(PriceList::class)
            ->find($id);
    }

    /**
     * @param int $priceListId
     * @return PriceRule[]
     */
    protected function getPriceRulesByPriceList($priceListId)
    {
        return $this->doctrine
            ->getManagerForClass(PriceRule::class)
            ->getRepository(PriceRule::class)
            ->findBy(['priceList' => $priceListId]);
    }

    /**
     * Check if node must be supported by validator
     * @param $node
     *
     * @return bool
     */
    protected function isSupportedNode($node)
    {
        return (($node instanceof NameNode) || ($node instanceof RelationNode));
    }
}
