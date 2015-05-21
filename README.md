# Google Webfonts API Cache

"Unlimited" API Requests.

Instead of

```
https://www.googleapis.com/webfonts/v1/webfonts?key=...
```

use

```
https://your-domain.com/webfonts/v1/webfonts
```

*At the moment are supported only simple GET requests.*

## Install

1. Install bundle with composer

    ```sh
    $ php composer.phar require "mmd/google-webfonts-cache":"dev-master" "sensio/buzz-bundle":"dev-master" "predis/predis":"dev-master"
    ```

2. Include bundle in `app/AppKernel.php`

    ```php
    $bundles = array(
        ...
        new Mmd\Bundle\GoogleWebfontsCacheBundle\MmdGoogleWebfontsCacheBundle(),
        new Sensio\Bundle\BuzzBundle\SensioBuzzBundle(),
    );
    ```

3. Include bundle's routing in `app/config/routing.yml`

    ```yml
    mmd_google_webfonts_cache:
        resource: "@MmdGoogleWebfontsCacheBundle/Resources/config/routing.yml"
        prefix:   /webfonts
    ```

4. Install [Redis](http://redis.io/).

    *You can use this [dockerfile](https://github.com/dockerfile/redis).*

5. Configure parameters in `app/config/parameters.yml`

    ```yml
    mmd_google_webfonts_cache.key: 'AIz...pWY'

    mmd_google_webfonts_cache.redis.scheme: 'tcp'
    mmd_google_webfonts_cache.redis.host: '127.0.0.1'
    mmd_google_webfonts_cache.redis.port: 6379
    mmd_google_webfonts_cache.redis.options: {} # https://github.com/nrk/predis#client-configuration
    ```