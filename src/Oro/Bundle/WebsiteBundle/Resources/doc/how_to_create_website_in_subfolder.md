Creating website in subfolder
=============================

Sometimes there is a need to get access to the website through subfolder. For example for multiple websites.

For implementation this feature you can use next approach:

1. Install application as usually.
2. Create subdirectory in web directotory (for example yoursitename).
3. Copy app.php from web directory into your new directory.
4. Modify ``app.php`` file into your folder.
    Update all requires in this file with relative paths adding additional ```/..``` in the beginning of path.
    
    For example:
    
        ```php
            require_once __DIR__.'/../app/AppKernel.php';
        ```
        
    should be changed to
        
        ```php
            require_once __DIR__.'/../../app/AppKernel.php';
        ```
        
    Add WEBSITE_PATH parameter to ServerBag before ```$response = $kernel->handle($request);``` This parameter value should be your website folder name. 
    
    ```php
        ...
        $request = Request::createFromGlobals();
        $request->server->add(['WEBSITE_PATH' => '/yoursitename']);
        $response = $kernel->handle($request);
        ...
    ```

Now when you refer to the address http://localhost/yoursitename/app_dev.php
asset files (styles.css, require.js etc) will be taken from main domain instead of subfolder.