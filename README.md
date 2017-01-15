# Symfony FloschProxyBundle
Authenticated symfony bundle: provides an authentication layer on top of a PHP proxy.

This bundle provides a User model and a YamlUserProvider, to authenticate users based on a yml file.
It also provides a Symfony command to add users with encrypted passwords.

Once the user is connected, it provides a PHP proxy thanks to [Guzzle PHP library][1], currently v6.

### Why?
This bundle is usefull if you want to proxy an HTTP application, with an authentication layer a bit stronger than http standards such as HTTP_BASIC.

### Security
**Please note that this bundle will be security-wise usefull if, and only if, you can provide an HTTPS certificate for your domain.**

### Requirements
 - Symfony 3.0 or above

### Installation
To install this bundle, run the command below and you will get the latest version from [Packagist][2].

``` bash
composer require flosch/proxy-bundle
```

### Usage
Load required bundles in AppKernel.php:
``` php
// app/AppKernel.php
public function registerBundles()
{
    $bundles = [
        // [...]
        new Flosch\Bundle\ProxyBundle\FloschProxyBundle()
    ];
}
```

Set up configuration

``` yaml
# app/config/config.yml
twig:
    # [...]
    globals:
        proxy_title: Your Proxy title

# FloschProxyBundle uses assetic (for the login page)
# However, you can override the templates and manage it yourself in any other way if you prefer.
assetic:
    # [...]
    bundles: [ FloschProxyBundle ]

flosch_proxy:
    base_url: http://127.0.0.1:8888/ # The URL you will actually proxy
    users_provider_file_path: "%kernel.root_dir%/../var/users.yml"
```

###### Style

For the login page, FloschProxyBundle uses an icons font called [Font Awesome][3].
If you wish to keep the icons of the login page, you will need to copy the fonts sources to the `web/fonts` folder of your project.
If you prefer not to use it, feel free to surcharge the login page template, by extending this bundle, as explained later in this doc.

Set up routing
# app/config/routing.yml or any other routing file
``` yaml
flosch_proxy:
    resource: "@FloschProxyBundle/Resources/config/routing.yml"
    prefix:   /
```

Set up security
``` yaml
# app/config/security.yml
security:
    encoders:
        # The FloschProxyBundle User model class, you can choose your favorite encoder
        Flosch\Bundle\ProxyBundle\Model\User:
            algorithm:            bcrypt
            cost: 17

    providers:
        # [...]
        proxy_users:
            id: flosch_proxy.provider.yaml_user_provider

    firewalls:
        # [...]
        flosch_proxy_app_login:
            pattern:  ^/login$
            security: false

        flosch_proxy_app:
            pattern:  ^/
            provider: proxy_users
            form_login:
                check_path: flosch_proxy_login_check_page
                login_path: flosch_proxy_login_page
                always_use_default_target_path: true
                default_target_path: /
            logout:
                path:   flosch_proxy_logout_page
                target: /

    access_control:
        - { path: ^/login, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/, roles: ROLE_USER }
```

### Authentication and users management
Here we are!

This bundle comes with a service called YamlUserProvider, providing users from a yaml file.
To add an access, add a new yaml array of this user's informations :

``` yml
# /path/of/your/users.yml
username:
    salt: # the salt used to encrypt the password, depending of the encoder you chosse, this might be optional (ex: bcrypt).
    password: # the encrypted password
```

You also have two commands to manage users from the console :

To create a new user :

``` bash
php bin/console flosch-proxy:users:create [username] [password] [--all]
```

Both username and password arguments are optionnal, the command will ask for it if you do not provide it.
The ````--override```` (or ````-o`````) option allow to replace an existing user's password (if the user does not exists, he will be created).

To remove an existing user :

``` bash
php bin/console flosch-proxy:users:remove [username] [--all]
```
Username argument is optionnal, the command will ask for it if you do not provide it.
The ````--all```` option allow to remove every existing users from the file.

### Extend the bundle
The Bundle itself provides the security layer, with login and logout routes ;
And a default login page, before "proxying" routes through the Guzzle client.

As a symfony bundle, you can extend it, to benefits of [Symfony inheritance][4],
Then override resources and / or controllers:

```
Controller/
    ProxyController.php --> Manage PHP proxy once authenticated
    Security/
        AuthenticationController.php --> Manage authentication
Resources/
    views/
        layout.html.twig --> Base template with HTML doctype
        Security/
            login.html.twig --> Login page template
```

All you need to do is setting up your own bundle as child of FloschProxyBundle:

``` php
// src/YourBundleName/YourBundleName.php
namespace YourBundleName;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class YourBundleName extends Bundle
{
    public function getParent()
    {
        return 'FloschProxyBundle';
    }
}
```

### Authors
 - Florent Schildknecht ([Portfolio][5])

### License
This bundle is released under the [MIT license](Resources/LICENSE)

 [1]: http://docs.guzzlephp.org/
 [2]: https://packagist.org/
 [3]: http://fontawesome.io/
 [4]: http://symfony.com/doc/3.2/cookbook/bundles/inheritance.html
 [5]: http://floschild.me
