# Adding New Storage Adapter

This document is a part of Utopia PHP contributors' guide. Before you continue reading this document make sure you have read the [Code of Conduct](../CODE_OF_CONDUCT.md) and the [Contributing Guide](../CONTRIBUTING.md).

## Getting Started

Storage adapters allows developers to add more types of storage device support easily through single interface.

## 1. Prerequisities

It's really easy to contribute to an open source project, but when using GitHub, there are a few steps we need to follow. This section will take you step-by-step through the process of preparing your own local version of Utopia PHP Storage, where you can make any changes without affecting Utopia PHP Storage right away.

> If you are experienced with GitHub or have made a pull request before, you can skip to [Implement new Storage Adapter](#2-implement-new-storage-adapter).

###  1.1 Fork the Appwrite repository

Before making any changes, you will need to fork Utopia PHP Storage's repository to keep branches on the official repo clean. To do that, visit the [Utopia PHP Storage Github repository](https://github.com/utopia-php/storage) and click on the fork button.

This will redirect you from `github.com/utopia-php/storage` to `github.com/YOUR_USERNAME/storage`, meaning all changes you do are only done inside your repository. Once you are there, click the highlighted `Code` button, copy the URL and clone the repository to your computer using `git clone` command:

```shell
$ git clone COPIED_URL
```

> To fork a repository, you will need a basic understanding of CLI and git-cli binaries installed. If you are a beginner, we recommend you to use `Github Desktop`. It is a really clean and simple visual Git client.

Finally, you will need to create a `feat-XXX-YYY-storage-adapter` branch based on the `master` branch and switch to it. The `XXX` should represent the issue ID and `YYY` the Storage adapter name.

## 2. Implement new Storage Adapter

### 2.1 Add new adapter and implement it

In order to start implementing new storage adapter, add new file inside `src/Storage/Device/YYY.php` where `YYY` is the name of the storage provider in `PascalCase`. Inside the file you should create a class that extends the basic `Device` abstract class. Note that the class name should start with a capital letter, as PHP FIG standards suggest.

## 3. Test your adapter

After you finished adding your new adapter, you should be able to use it. You should then write a test for it. In order to write a test, first create new file inside `tests/Storage/Device/YYYTest.php` where `YYY` is the name of the storage adapter from previous step. Taking one of the existing device as a reference implement all the tests for newly added adapter.

### 3.1 Running and testing locally

In order to run automated tests, you should have PHP installed on your system. To run the test, you can simply enter the following command in your terminal.

```
vendor/bin/phpunit tests/Storage/Device/YYYTest.php
```

This command will run the tests you added for your adapter. If everything goes well, raise a pull request and be ready to respond to any feedback which can arise during our code review.

## 4. Raise a pull request

First of all, commit the changes with the message `Added YYY Storage adapter` and push it. This will publish a new branch to your forked version of Utopia PHP Storage. If you visit it at `github.com/YOUR_USERNAME/storage`, you will see a new alert saying you are ready to submit a pull request. Follow the steps GitHub provides, and at the end, you will have your pull request submitted.

## 🤕 Stuck ?
If you need any help with the contribution, feel free to head over to [our discord channel](https://appwrite.io/discord) and we'll be happy to help you out.
