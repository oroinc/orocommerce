<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Oro\Bundle\PricingBundle\Expression\ExpressionParser;
use Oro\Bundle\PricingBundle\Expression\NameNode;
use Oro\Bundle\PricingBundle\Expression\RelationNode;
use Oro\Bundle\PricingBundle\Expression\Preprocessor\ExpressionPreprocessorInterface;

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
     * @var ExpressionPreprocessorInterface
     */
    protected $preprocessor;

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
     * @var bool
     */
    public $isValid = true;

    /**
     * @param ExpressionParser $expressionParser
     * @param ExpressionPreprocessorInterface $preprocessor
     * @param RegistryInterface $doctrine
     */
    public function __construct(
        ExpressionParser $expressionParser,
        ExpressionPreprocessorInterface $preprocessor,
        RegistryInterface $doctrine
    ) {
        $this->expressionParser = $expressionParser;
        $this->preprocessor = $preprocessor;
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
                $expressions = [$this->getFieldExpression($object, $field)];
                $this->references[$field] = [$object->getId()];
                while (true) {
                    $nodes = $this->parseExpression($expressions);
                    $references = $this->findReferences($nodes);

                    if ($this->hasCircularReference($references)) {
                        $this->context->buildViolation($constraint->message)->atPath($field)->addViolation();
                        break;
                    }

                    $this->references = array_merge_recursive($this->references, $references);
                    if (count($references) === 0) {
                        break;
                    }
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
        foreach ($references as $field => $referenceIds) {
            foreach ($referenceIds as $id) {
                if (array_key_exists($field, $this->references) && in_array($id, $this->references[$field], true)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param array $expressions
     * @return array
     */
    protected function parseExpression($expressions)
    {

        $nodes = [];
        foreach ($expressions as $expression) {
            $expression = $this->preprocessor->process($expression);
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
            if (empty($containerId)) {
                continue;
            }

            $field = $node->getField();

            if ($this->container === null) {
                $this->container = $node->getContainer();
            }

            if (empty($references[$field])) {
                $references[$field] = [];
            }

            if (!in_array($containerId, $references[$field], true)) {
                $references[$field][] = $containerId;
            }
        }
        return $references;
    }

    /**
     * @param array $references
     * @return array
     */
    protected function findExpressions($references)
    {
        $expressions = [];

        foreach ($references as $field => $referenceIds) {
            foreach ($referenceIds as $id) {
                $entity = $this->getEntity($id);
                $expression = $this->getFieldExpression($entity, $field);
                if ($expression !== null) {
                    $expressions[] = $expression;
                }
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
    protected function getFieldExpression($entity, $field)
    {
        $expression = null;
        if ($this->accessor->isReadable($entity, $field)) {
            $expression = $this->accessor->getValue($entity, $field);
        }
        return $expression;
    }

    /**
     * @param int $id
     *
     * @return object
     */
    protected function getEntity($id)
    {
        return $this->doctrine
            ->getManagerForClass($this->container)
            ->getRepository($this->container)
            ->find($id);
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
