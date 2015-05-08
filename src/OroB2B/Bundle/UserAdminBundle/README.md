OroB2B\Bundle\UserAdminBundle\OroB2BUserAdminBundle
===================================================

Description:
------------

This bundle provides a way to handle frontend users roles and capabilities from admin.

Bundle responsibilities:
------------------------

Adding new capabilities.
Frontend Roles CRUD.
Frontend User CRUD.
Possibility to collect capabilities in groups and assign users to groups.
Activate and deactivate Frontend users.
Send welcome email.
Password edit and automatic password generation for new Frontend User.

Expected dependencies:
----------------------

\Swift_Mailer
\Swift_Message
Doctrine\Common\Collections\ArrayCollection
Doctrine\Common\DataFixtures\AbstractFixture
Doctrine\Common\Persistence
Doctrine\DBAL\Schema\Schema
Doctrine\ORM\Mapping
FOS\RestBundle\Controller\Annotations
FOS\RestBundle\Routing\ClassResourceInterface
FOS\RestBundle\Util\Codes
FOS\UserBundle\Entity\Group
FOS\UserBundle\Util\TokenGenerator
Gedmo\Mapping\Annotation
Nelmio\ApiDocBundle\Annotation\ApiDoc
Oro\Bundle\ConfigBundle\Config\ConfigManager
Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface
Oro\Bundle\DataGridBundle\Extension\Sorter\OrmSorterExtension
Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository
Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture
Oro\Bundle\EmailBundle\Model\EmailTemplateInterface
Oro\Bundle\EmailBundle\Provider\EmailRenderer
Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config
Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension
Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType
Oro\Bundle\MigrationBundle\Migration
Oro\Bundle\NavigationBundle
Oro\Bundle\SearchBundle
Oro\Bundle\SecurityBundle\Annotation
Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController
Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase
Oro\Bundle\TestFrameworkBundle\Test\WebTestCase
Oro\Component\Testing\Unit\EntityTestCase
Oro\Component\Testing\Unit\FormHandlerTestCase
OroB2B\Bundle\UserBundle\Entity\AbstractUser
Sensio\Bundle\FrameworkExtraBundle\Configuration\Route
Sensio\Bundle\FrameworkExtraBundle\Configuration\Template
Symfony\Bundle\FrameworkBundle\Controller\Controller
Symfony\Component\Config\FileLocator
Symfony\Component\DependencyInjection\ContainerBuilder
Symfony\Component\DependencyInjection\Loader
Symfony\Component\Form
Symfony\Component\HttpFoundation
Symfony\Component\HttpKernel
Symfony\Component\OptionsResolver\OptionsResolverInterface
Symfony\Component\Routing\Annotation\Route
Symfony\Component\Translation\TranslatorInterface
Symfony\Component\Validator\Validation
