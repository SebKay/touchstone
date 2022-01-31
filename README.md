# Touchstone

[![PHP](https://github.com/SebKay/touchstone/actions/workflows/php.yml/badge.svg)](https://github.com/SebKay/touchstone/actions/workflows/php.yml)

A modern wrapper around the [official WordPress testsuite](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/). It can be used to run both Unit and Integration tests.

---

## Installation

Run the following command to install Touchstone in your project:

```shell
composer require sebkay/touchstone --dev
```

---

## Usage

### 1.) Setup

Running the setup process downloads and installs both WordPress and the official WordPress test files in your temp directory.

```shell
# Options
./vendor/bin/touchstone setup --db-host=[HOST] --db-socket=[SOCKET PATH] --db-name=[DATABASE NAME] --db-user=[DATABASE USERNAME] --db-pass=[DATABASE PASSWORD] --skip-db-creation=[TRUE/FALSE]
```

#### Regular Connection

```shell
# Example
./vendor/bin/touchstone setup --db-host=127.0.0.1:8889 --db-name=touchstone_tests --db-user=root --db-pass=root
```

#### via a Socket

```shell
./vendor/bin/touchstone setup --db-host=127.0.0.1:8889 --db-socket="/path/to/mysqld.sock" --db-name=touchstone_tests --db-user=root --db-pass=root
```

### 2.) Creating Tests

All your tests will need to be in the following structure from the root of your project:

```shell
tests/
    Unit/
        ExampleUnitTest.php
    Integration/
        ExampleIntegrationTest.php
```

All your Unit tests will need to extend the `WPTS\Tests\UnitTest` class and all your integrationt tests will need to extend the `WPTS\Tests\IntegrationTest` class.

Here's an example Unit test:

```php
<?php

namespace WPTS\Tests\Unit;

class ExampleUnitTest extends UnitTest
{
    public function test_it_works()
    {
        $this->assertTrue(true);
    }
}
```

Here's an example Integration test:

```php
<?php

namespace WPTS\Tests\Integration;

class ExampleIntegrationTest extends IntegrationTest
{
    public function test_post_title_was_added()
    {
        $post_id = $this->factory()->post->create([
            'post_title' => 'Example post title',
        ]);

        $post = \get_post($post_id);

        $this->assertSame('Example post title', $post->post_title);
    }
}
```

### 3.) Running Tests

You can run either all of your tests or a single testsuite with the following commands:

```shell
# Run all tests
./vendor/bin/touchstone test

# Run Unit tests
./vendor/bin/touchstone test --type=unit

# Run Integration tests
./vendor/bin/touchstone test --type=integration
```

### 4.) Configuration

You can configure certain things by creating a `config.touchstone.php` file in the root of your project.

#### Directories For Tests

Here's how to set the directories for where your tests are located:

```php
<?php
# config.touchstone.php

return [
    'directories' => [
        'all'         => 'tests',
        'unit'        => 'tests/Unit',
        'integration' => 'tests/Integration',
    ],
];
```

#### WordPress Plugins

Here's how to load plugins which are loaded before each test.

This means for a plugin like ACF (Advanced Custom Fields) you can use functions like `get_field()` in your code and it won't break your tests.

**Important:** You will need to provide the plugin files. I recommend putting them all in `bin/plugins/` in your theme/plugin and the adding that path to your `.gitignore`.

```php
<?php
# config.touchstone.php

return [
    'plugins' => [
        [
            'name' => 'Advanced Custom Fields',
            'file' => dirname(__FILE__) . '/bin/plugins/advanced-custom-fields-pro/acf.php',
        ],
    ],
];
```

```gitignore
# Directories
bin/plugins
```

#### WordPress Theme

Here's how to load a theme which is active for each test.

```php
<?php
# config.touchstone.php

return [
    'theme' => [
        'root' => dirname(__FILE__) . '/../../themes/twentytwentyone',
    ],
];
```

---

## Composer Scripts

You can create Composer scripts so you don't need to memorise the above commands.

To do so add the following to your `composer.json` file:

```json
...
    "scripts": {
        "touchstone:setup": "./vendor/bin/touchstone setup --db-host=[HOST] --db-name=[DATABASE NAME] --db-user=[DATABASE USER] --db-pass=[DATABASE PASSWORD] --skip-db-creation=true",
        "touchstone:test": "./vendor/bin/touchstone test",
        "touchstone:unit": "./vendor/bin/touchstone test --type=unit",
        "touchstone:integration": "./vendor/bin/touchstone test --type=integration"
    }
...
```

Then you can run the following from the command line:

```shell
# Run setup
composer touchstone:setup

# Run all tests
composer touchstone:test

# Run Unit tests
composer touchstone:unit

# Run Integration tests
composer touchstone:integration
```

---

## Troubleshooting

### Tests Won't Run

If you ever have problems running your tests, run the `setup` command. It's more than likely you've restarted your machine since the last time you ran the tests which deletes the WordPress test files. Re-running the setup process will usually fix the problem.

### Why Does This Exist?

The official way of running the WordPress testsuite is horribly complicated and incredibly prone to user error.

Touchstone fixes both of those issues by making the process of creating and running tests easy.
