<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Model\Action;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ActionBundle\Model\ActionContext;
use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;
use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;

abstract class CategoryCaseActionTestCase extends WebTestCase
{
    /**
     * @var ActionContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var AbstractAction
     */
    protected $action;

    public function setUp()
    {
        $this->initClient();

        $this->disableActions();

        $this->loadFixtures([
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures'
            . '\LoadProductVisibilityResolvedFallbackCategoryData'
        ]);

        $this->context = $this->getMock('Oro\Bundle\ActionBundle\Model\ActionContext');

        $this->action = $this->getContainer()->get($this->getActionContainerId());
    }

    protected function disableActions()
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroWorkflowBundle:ProcessDefinition');
        $definitions = $em->getRepository('OroWorkflowBundle:ProcessDefinition')
            ->findAll();

        foreach ($definitions as $definition) {
            $definition->setEnabled(false);
        }

        $em->flush();
    }

    /**
     * @return string
     */
    abstract protected function getActionContainerId();

    /**
     * @param array $expectedData
     */
    protected function assertProductVisibilityResolvedCorrect(array $expectedData)
    {
        $this->assertEquals($expectedData, [
            'hiddenProducts' => $this->getHiddenProducts(),
            'hiddenProductsByAccountGroups' => $this->getHiddenProductsByAccountGroups(),
            'hiddenProductsByAccounts' => $this->getHiddenProductsByAccounts(),
        ]);
    }

    /**
     * @return array
     */
    protected function getHiddenProducts()
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
        $queryBuilder = $repository->createQueryBuilder('pvr');
        $this->selectHiddenProductSku($queryBuilder, 'pvr');
        $results = $queryBuilder->getQuery()
            ->getScalarResult();

        return array_map(function ($row) {
            return $row['sku'];
        }, $results);
    }

    /**
     * @return array
     */
    protected function getHiddenProductsByAccountGroups()
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
        $queryBuilder = $repository->createQueryBuilder('agpvr')
            ->select('accountGroup.name as account_group_name')
            ->join('agpvr.accountGroup', 'accountGroup');
        $this->selectHiddenProductSku($queryBuilder, 'agpvr');
        $results = $queryBuilder->getQuery()
            ->getScalarResult();

        return array_reduce($results, function ($results, $row) {
            if (!isset($results[$row['account_group_name']])) {
                $results[$row['account_group_name']] = [];
            }
            $results[$row['account_group_name']][] = $row['sku'];
            return $results;
        }, []);
    }

    /**
     * @return array
     */
    protected function getHiddenProductsByAccounts()
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved');
        $queryBuilder = $repository->createQueryBuilder('apvr')
            ->select('account.name as account_name')
            ->join('apvr.account', 'account');
        $this->selectHiddenProductSku($queryBuilder, 'apvr');
        $results = $queryBuilder->getQuery()
            ->getScalarResult();

        return array_reduce($results, function ($results, $row) {
            if (!isset($results[$row['account_name']])) {
                $results[$row['account_name']] = [];
            }
            $results[$row['account_name']][] = $row['sku'];
            return $results;
        }, []);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     */
    protected function selectHiddenProductSku(QueryBuilder $queryBuilder, $alias)
    {
        $queryBuilder->addSelect('product.sku')
            ->join($alias . '.product', 'product')
            ->andWhere($queryBuilder->expr()->eq(
                $alias . '.visibility',
                BaseProductVisibilityResolved::VISIBILITY_HIDDEN
            ))
            ->orderBy($alias . '.categoryId');
    }
}
