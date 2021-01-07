# Updater

![license](https://img.shields.io/badge/license-Apache%202-blue)
[![build](https://github.com/jamielsharief/updater/workflows/CI/badge.svg)](https://github.com/jamielsharief/updater/actions)
[![coverage status](https://coveralls.io/repos/github/jamielsharief/updater/badge.svg?branch=main)](https://coveralls.io/github/jamielsharief/updater?branch=main)

Updater is an application updater, it will update and upgrade your deployed applications to the latest release running any tasks before and after each update that are specific to each each release.

Whilst this was originally designed so that applications can be updated by clients or automatically (through a CRON job), I think this could be interesting in the deployment/release cycle.

It works with [composer](https://getcomposer.org/) repositories such as [packagist.org](https://packagist.org/) or static repositories generated by [satis](https://getcomposer.org/doc/articles/handling-private-packages.md). Private repositories can be protected using authentication and update will store and request when needed.

```
$ updater update
     __  __          __      __
    / / / /___  ____/ /___ _/ /____  _____
   / / / / __ \/ __  / __ `/ __/ _ \/ ___/
  / /_/ / /_/ / /_/ / /_/ / /_/  __/ /
  \____/ .___/\__,_/\__,_/\__/\___/_/
      /_/

version 0.1.0

- Checking for updates company/app (1.0.2)
- Downloading company/app (1.0.3)
- Running before scripts:
 > bin/console db:backup
- Extracting company/app (1.0.3)
- Running after scripts:
 > composer update
 > bin/console db:migrate
```

## Installation

You can build from source or download `updater` from the [releases](https://github.com/jamielsharief/updater/releases) section.

### Download the Source Code

Download the source and build `updater` application.

```bash
$ git clone http://github.com/jamielsharief/updater updater
$ cd updater
$ composer install
```

Now build the PHAR, this will create `bin/updater.phar`, you can copy this into your application `bin` folder.

```bash
$ php -d phar.readonly=Off bin/build
```

### Copy globally

To install globally copy

```bash
$ cp bin/updater.phar /usr/local/bin/updater
```

## Usage

Create `updater.json` in your application project folder, and in each release you can configure it to run `before` and `after` commands, bash or PHP scripts - if and when needed.

```json
{
    "url": "https://packagist.org",
    "package": "company/app",
    "scripts": {
        "before": [
            "bin/console db:backup"
        ],
        "after": [
            "bin/console db:migrate"
        ]
    }
}
```

If you are using a private [satis repository](https://getcomposer.org/doc/articles/handling-private-packages.md), change the `url` in the `updater.json` , e.g. `https://www.example.com` and setup authentication for `composer`, if required.


### Authentication

Currently only `http-basic` authorization is supported, to work with this simply create the `auth.json` in your project directory. However you can also generate a satis private repository which can then connect to various repositories and supports more authentication methods.

```json
{
    "http-basic": {
        "example.com": {
            "username": "token",
            "password": "878ec3cebea5b2c1ee4c0becdb00d3d3"
        },
    }
}
```

> Composer can create this for you as well `composer config http-basic.example.com username password --global`


### Initializing the Project

To get started with `updater` you need to initialize the project.

Run the `updater init` command to initialize the updater, you will be prompted for the current version, and updates after this will be pulled.

```bash
$ updater init
```

### Updating

To update your application just run the following command, which will run all updates that are available in sequence.


```bash
$ updater update
```

If you want to run all available updates in the current major version

```bash
$ updater update --all
```

> You can use the `--verbose` option to see the output of the scripts, if any

If you want to test the update using a specific version or branch, this will use the specific version and it will
not update the lock file.

```bash
$ updater update --version dev-master
```

### Upgrading

> This will only upgrade to the next major version if there are no pending updates, and it will not update any versions after the first next major version.

To upgrade to the next major release run the `upgrade` command.

```bash
$ updater upgrade
```

> You can use the `--verbose` option to see the output of the scripts, if any

If you want to test the upgrade using a specific version or branch, this will use the specific version and it will
not update the lock file.

```bash
$ updater upgrade --version dev-master
```

## Demo

> Building the PHAR will fail if you do not remove this directory when you have finished.

Download the source and dependencies

```bash
$ git clone http://github.com/jamielsharief/updater updater
$ cd updater
$ composer install
```

Now download the sample project into a sub folder, e.g. `updater/demo` so you can run updater without
building the PHAR file.

```bash
$ composer create-project jamielsharief/updater-demo:0.1.0 demo
```

The first version does not have the `updater.json`, so create this in `demo` folder, and set the
URL to the repository.

```json
{
    "url": "https://packagist.org",
    "package": "jamielsharief/updater-demo",
    "scripts": {
        "before": [],
        "after": []
    }
}
```

Initialize `updater`, mentioning the directory where you extracted the zip archive too.

```bash
$ bin/updater init demo --version 0.1.0
```

Then run the `update` command to get the next available update

```bash
$ bin/updater update demo
```

Now, you can run it a few times or use the `--all`.

Once you are ready to upgrade the application to the next major version, run

```bash
$ bin/updater upgrade demo
```