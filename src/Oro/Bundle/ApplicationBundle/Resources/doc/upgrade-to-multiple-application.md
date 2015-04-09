Upgrade to multiple application approach
========================================

If developer wants to update regular simple application to [multiple application approach](./getting-started.md#directory-structure),
then he has to do following actions:

1. Install or update application
--------------------------------

**If application is not installed**

Run the `composer update` command

```
$ php composer.phar update
```

Parameters started with `application_host` should contain URLs for appropriate entry points. For example:

```
application_host.admin ('http://localhost/admin.php'): http://your-site-name/admin.php
application_host.frontend ('http://localhost/'): http://your-site-name/
application_host.install ('http://localhost/install.php'): http://your-site-name/install.php
application_host.tracking ('http://localhost/tracking.php'): http://your-site-name/tracking.php
```

Remove `app/config` folder:

```
$ rm -rf app/config
```

**If application already installed**

Move parameter file `app/parameters.yml` to `app/common/parameters.yml`. The same should be done for other parameter
files.

Add entry point parameters to the end of file. For example:

```
application_host.admin: 'http://your-site-name/admin.php'
application_host.frontend: 'http://your-site-name/frontend.php'
application_host.install: 'http://your-site-name/install.php'
application_host.tracking: 'http://your-site-name/tracking.php'
```

If application has some custom configuration in `app/config` directory then it has to be moved to appropriate files
in `app/admin/config` directory. The same should be done for custom resources - they have to be moved from
`app/Resources` to `app/admin/Resources`.

After that `app/config` directory should be removed:

```
$ rm -rf app/config
```

2. Move attachment files
------------------------

Copy attachment files from `app/attachment` to `var/attachment`:
```
$ cp -r app/attachment/* var/attachment
```

Then remove `app/attachment` directory:
```
$ rm -rf app/attachment
```

3. Remove cache and logs directories
------------------------------------

Now application log files are located in `var/logs/<application_name>_<application_env>.log` files.
If there is a need to save old log files - please, backup content of `app/logs` directory or move them from `app/logs` 
to `var/logs` directory.

Then directories that contain cache and logs should be removed:

```
$ rm -rf app/cache app/logs
```

4. Update Oro Platform
----------------------

Run `oro:platform:update` command

```
$ php app/console oro:platform:update --force
```

5. Make sure `var` directory is writable
----------------------------------------

Make sure `var` directory is writable both for the web server and the command line user.
If web server user is different from command line user, developer can use 
[regular Symfony approach](http://symfony.com/doc/2.3/book/installation.html#book-installation-permissions)
to handle permissions.

6. Update web service configuration
-----------------------------------

Change web server settings according to application hosts defined in `app/common/parameters.yml` - i.e. developer can 
set different hosts/aliases for different entry points and these hosts/aliases have to be set in parameters file.

Now there are separate entry points for each application. Also developer can 
[add new application](./add-new-application.md) for some custom purposes.

Optional changes
----------------

This part is not required - described changes are necessary only if developer made custom changes in single application.

**`AppKernel` file was changed**

If developer changed `AppKernel` file, then these changes should be merged with new `AppKernel` file.

**`DistributionKernel` and `dist` console file were removed**

Now `install` application uses regular `AppKernel` and `console` files instead of `DistributionKernel` and `dist` files.
If developer changed `DistributionKernel` or `dist` files, then these changes should be merged with 
`AppKernel` and `console` files.
