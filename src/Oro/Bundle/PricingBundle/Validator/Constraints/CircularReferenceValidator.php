<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PricingBundle\Expression\NameNode;
use Oro\Bundle\PricingBundle\Expression\RelationNode;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\PricingBundle\Expression\ExpressionParser;

class CircularReferenceValidator extends ConstraintValidator
{
    /**
     * @var ExpressionParser
     */
    protected $expressionParser;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    protected $references = [];

    protected $container = null;

    /**
     * @var EntityRepository
     */
    protected $entityRepository;

    /**
     * @param ExpressionParser $expressionParser
     */
    public function __construct(ExpressionParser $expressionParser, ManagerRegistry $doctrine)
    {
        $this->expressionParser = $expressionParser;
        $this->doctrine = $doctrine;
    }

    public $isValid = true;

    /**
     * @param $value
     * @param LogicalExpression $constraint
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
                $expressions = [$this->getFieldExpression($object, ('get' . ucfirst($field)))];
                $this->references[$field][] = $object->getId();
                while (true) {
                    $nodes = $this->parseExpression($expressions);

                    $references = $this->findReferences($nodes);

                    if ($this->isValid === false) {
                        $this->context->buildViolation($constraint->message)->atPath($field)->addViolation();
                    }
                    if (count($references) === 0) {
                        break;
                    }
                    $expressions = $this->findExpressions($references);
                }
            }
        } catch (SyntaxError $ex) {
            $this->context->buildViolation($constraint->invalidNodeMessage)->atPath($field)->addViolation();
        }
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
            if (!$this->isNode($node)) {
                continue;
            }

            $containerId = $node->getContainerId();

            if (empty($containerId)) {
                continue;
            }

            $field = $node->getField();

            if (array_key_exists($field, $this->references) && in_array($containerId, $this->references[$field])) {
                $this->isValid = false;
                return [];
            }

            if ($this->container === null) {
                $this->setContainer($node->getContainer());
                $this->setEntityRepository();
            }

            if (empty($references[$field])) {
                $references[$field] = [];
            }

            if (!in_array($containerId, $references[$field])) {
                $references[$field][] = $containerId;
            }
        }
        $references = array_unique($references);
        $this->references = array_merge_recursive($this->references, $references);
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
            $methodName = 'get' . ucfirst($field);
            foreach ($referenceIds as $id) {
                $entity = $this->getEntity($id);
                $expression = $this->getFieldExpression($entity, $methodName);
                if ($expression !== null) {
                    $expressions[] = $expression;
                }
            }
        }
        return $expressions;
    }

    /**
     * @param $entity
     * @param string $methodName
     *
     * @return string
     */
    protected function getFieldExpression($entity, $methodName)
    {
        $expression = null;
        if (method_exists($entity, $methodName)) {
            $expression = call_user_func(array($entity, $methodName));
        }
        return $expression;
    }

    /**
     * @param string $containerName
     */
    protected function setContainer($containerName)
    {
        $this->container = $containerName;
    }

    protected function setEntityRepository()
    {
        $this->entityRepository = $this->doctrine
            ->getManagerForClass($this->container)
            ->getRepository($this->container);
    }

    /**
     * @param int $id
     *
     * @return object
     */
    protected function getEntity($id)
    {
        return $this->entityRepository->find($id);
    }

    /**
     * Check if node must be checked by validator
     * @param $node
     *
     * @return bool
     */
    protected function isNode($node)
    {
        return (($node instanceof NameNode) || ($node instanceof RelationNode));
    }
}
