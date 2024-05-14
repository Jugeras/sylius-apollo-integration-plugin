# Apollo integration plugin for Sylius

## Installation

### Step 1: Prepare composer.json file

Open `composer.json` and add:
```
...
  "repositories": [
        ...
        {
            "type": "vcs",
            "url":  "https://github.com/Jugeras/sylius-apollo-integration-plugin.git"
        }
  ],
  "minimum-stability": "dev",
...
```

### Step 2: Install plugin

This plugin requires [Sylius Brand Pugin](https://packagist.org/packages/loevgaard/sylius-brand-plugin).  
This plugin requires [PrintPlius B2B Pugin](https://github.com/Jugeras/sylius-b2b-plugin).

Open a command console, enter your project directory and execute the following command to download the latest stable version of this plugin:

```bash
$ composer require printplius/apollo-integration-plugin --no-cache
```

### Step 3: Enable the plugin

Then, enable the plugin by adding the following to the list of registered plugins/bundles
in the `config/bundles.php` file of your project:

```php
<?php

return [
    // ...
    
    PrintPlius\SyliusApolloIntegrationPlugin\PrintPliusSyliusApolloIntegrationPlugin::class => ['all' => true],
    
    // ...
];
```

### Step 4: Add config

Then, add settings in the `config/packages/sylius.yaml` file of your project:

```yaml
imports:
  ...
  - { resource: "@PrintPliusSyliusApolloIntegrationPlugin/Resources/config/config.yml" }
```

### Step 5: Add routes

Then, add routes in the `config/routes.yaml` file of your project:

```yaml
...
printplius_sylius_apollo_integration:
  resource: "@PrintPliusSyliusApolloIntegrationPlugin/Resources/config/routing.yml"
...
```

### Step 6: Set env variables

Open file `.env` OR `.env.local`

```dotenv
APOLLO_URL=
APOLLO_LICKEY=
```

### Step 7: Update database schema

Use Doctrine migrations to create a migration file and update the database.

```bash
$ bin/console doctrine:migrations:diff
$ bin/console doctrine:migrations:migrate
```

