<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Model;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Model\ProductVisibilityQueryBuilderModifier;

class ProductVisibilityQueryBuilderModifierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductVisibilityQueryBuilderModifier
     */
    protected $modifier;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()->getMock();

        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');

        $this->modifier = new ProductVisibilityQueryBuilderModifier($this->configManager, $this->tokenStorage);
    }

    public function testVisibilitySystemConfigurationPathNotSet()
    {
        $message = sprintf('%s::visibilitySystemConfigurationPath not configured', get_class($this->modifier));
        $this->setExpectedException('\LogicException', $message);
        $this->modifier->modify($this->queryBuilder);
    }
}
