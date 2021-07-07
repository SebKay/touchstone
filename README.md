# Touchstone

An easy to use tool for running WordPress unit and integration tests.

## Usage

### 1.) Setup

Install the WordPress test files and create the database used for the integration tests.

```shell
# Script
./vendor/bin/touchstone setup <db-name> <db-user> <db-password> <db-host>

# Example
./vendor/bin/touchstone setup wp_tests root root 127.0.0.1:8889
```

If you've already run the setup before then you can skip the database creation and just install the WordPress test files like so:

```shell
# Script
./vendor/bin/touchstone setup <db-name> <db-user> <db-password> <db-host> <skip-db-creation>

# Example
./vendor/bin/touchstone setup wp_tests root root 127.0.0.1:8889 true
```

**Important**: You'll have to skip database creation every time you restart you computer as the test files are stored in a temporary directory.

### 2.) Run Tests

#### Unit

To run unit tests you can pass `unit` to the Touchstone script:

```shell
./vendor/bin/touchstone unit
```

#### Integration

To run integration tests you can pass `integration` to the Touchstone script:

```shell
./vendor/bin/touchstone integration
```

#### All

To run all tests at once you can pass `test` to the Touchstone script:

```shell
./vendor/bin/touchstone test
```
