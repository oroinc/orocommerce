New application creating
========================

For creating new application you have got to do three simple actions:

###1. Add your application entry points to web folder.

For example `someapplication.php` for production entry point and `someapplication_dev.php` for development entry point.
As as an example you can use any of existing entry points pairs (`admin.php` and `admin_dev.php` or `frontend.php` and `frontend_dev.php`)
You have got only set application name to kernel object.
```php
$kernel->setApplication('someapplication');
```

###2. Add your application entry points to web folder.

Add folder called as application name to app with config for your own application with following structure.

```
|── someapplication/
│   ├── config/
│   │   ├── config_dev.yml
│   │   ├── config_prod.yml
│   │   ├── config_test.yml
│   │   ├── config.yml
│   │   ├── parameters_dev.yml
│   │   ├── parameters_prod.yml
│   │   ├── parameters_test.yml
│   │   ├── parameters.yml
│   │   ├── routing_dev.yml
│   │   ├── routing_prod.yml
│   │   ├── routing_test.yml
│   │   ├── routing.yml
│   │   ├── security_dev.yml
│   │   ├── security_prod.yml
│   │   ├── security_test.yml
│   │   └── security.yml
|   └── Resources/
```

You can optional export common configs from app/common folder. There are general configs for all applications.
General resources (views, styles, js e.t.c) for your application you can put into someapplication/Resources

###3. Add application default bundles to `oro/bundles.yml`.

Use your application name as suffix. In this case it will be `bundles_someapplication node`. Use `bundles_someapplication` node in all your bundles.

```yml
bundles_someapplication:
    - { name: Symfony\Bundle\FrameworkBundle\FrameworkBundle }
    - { name: Symfony\Bundle\SecurityBundle\SecurityBundle }
    - { name: Symfony\Bundle\TwigBundle\TwigBundle }
    - { name: Symfony\Bundle\MonologBundle\MonologBundle }
    - { name: Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle }
    - { name: Symfony\Bundle\AsseticBundle\AsseticBundle }
    - { name: Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle }
    - { name: Doctrine\Bundle\DoctrineBundle\DoctrineBundle }
    - { name: Oro\Bundle\ApplicationBundle\OroApplicationBundle, priority: 10 }
```

Presence of Oro\Bundle\ApplicationBundle\OroApplicationBundle in your bundles list is requirement. Its capabilities will be helpful for directly link between several applications.
For example you can create link from you someapplication to frontend or admin application which already available in system. In this bundle also override some Symfony2 default commands.

That's all. Now you can launch your own application.
