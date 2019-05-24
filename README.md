# Memento MediaWiki Extension

The Memento MediaWiki extension makes it as straightforward to access the past versions of MediaWiki articles as it is to access their current version.

The Memento framework allows you to see versions of articles as they existed at some date in the past. With a browser plug-in, like [Memento for Chrome](https://chrome.google.com/webstore/detail/memento-time-travel/jgbfpjledahoajcppakbgilmojkaghgm?hl=en), you can enter a URL of an article in your browser and specify the desired date to view the past version of that article. This way you can browse the Web of the past. The Memento extension will present to you a version of the article as it existed on or close to the selected date. Normally, this will only work if previous (archived) versions are available on the Web. Fortunately, MediaWiki is a Content Management System which implies that it maintains all revisions made to an article. This extension leverages this archiving functionality and provides native Memento support for MediaWiki.

This package contains the source code, build scripts, and tests for the Memento MediaWiki Extension.

This file also contains installation information, but more comprehensive information about the extension is at: https://www.mediawiki.org/wiki/Extension:Memento

The extension is also featured in the following academic work:

Jones, S.M., Nelson, M.L. & Van de Sompel, H. Avoiding spoilers: wiki time travel with Sheldon Cooper. *International Journal on Digital Libraries* (2018) 19: 77. https://doi.org/10.1007/s00799-016-0200-8

Jones, S.M. & Nelson, M.L. Avoiding Spoilers in Fan Wikis of Episodic Fiction. Norfolk, Virginia; 2015. arXiv:1506.06279. Available from: https://arxiv.org/abs/1506.06279v1

Jones, S.M., Nelson, M.L., Shankar, H. & Van de Sompel, H. Bringing Web Time Travel to MediaWiki: An Assessment of the Memento MediaWiki Extension. Norfolk, Virginia; 2014. arXiv:1406.3876. Available from: https://arxiv.org/abs/1406.3876v1

## Installation

To install this package within MediaWiki perform the following:
* copy the ``Memento`` directory into the extensions directory of your MediaWiki installation
* add the following to the LocalSettings.php file in your MediaWiki installation:
```
wfLoadExtension( 'Memento' );
```

## Configuration

This extension has sensible defaults, but also allows the following settings to be added to LocalSettings.php in order to alter its behavior:

* `$wgMementoTimemapNumberOfMementos` - allows the user to alter the number of Mementos included in a TimeMap served up by this extension (default is 500)

* `$wgMementoIncludeNamespaces` - is an array of MediaWiki Namespace IDs (e.g. the integer values for Talk, Template, etc.) to include for Mementofication, default is an array containing just 0 (Main); the list of MediaWiki Namespace IDs is at https://www.mediawiki.org/wiki/Manual:Namespace

## Packaging

To package the Memento MediaWiki Extension, type the following 
from this directory:

```
$ make package
```

This serves to run everything needed to verify the code and package the zip for release.

## Automated Deployment and Testing

### Using Docker

Easier testing with supported MediaWiki versions is now available via Docker. First change into the directory containing the docker-compose files:

```
$ cd tests/docker-image
```


Then decide which version of MediaWiki to test against. We currently test against:
* 1.31.1
* 1.32.1

Run the following to start the container for 1.31.1:

```
$ ./starttestdocker.sh 1.31.1
```

**Do not forget this step!** Run the following to load the database:

```
$ docker exec docker-image_database_1 /bin/bash -c /loaddb.sh
```

This extra manual step is necessary because the script does not yet know when the database has fully started.

Change back to the directory at the top of the repository:

```
$ cd ../../
```

Run the tests as stated in the **Integration Testing** section.


### Using your own MediaWiki Installation

To deploy the Memento MediaWiki Extension locally for testing, one must first indicate to the shell where MediaWiki is installed, then run the appropriate make target.

```
$ export MWDIR=<where your MediaWiki is installed>
$ make deploy
```

To remove the software from a MediaWiki instance, type:

```
$ make undeploy
```

## Setting Up Testing and Code Compliance

If you have [composer](https://getcomposer.org/) installed, you can install this version of PHPUnit and PHP Code Sniffer by running:

```
$ php /path/to/composer install
$ export PATH=$PATH:`pwd`/vendor/bin
```

If do not have [composer](https://getcomposer.org/), you will need to ensure that [PHP Unit](https://phpunit.de/) (`phpunit`) and [PHP Code Sniffer](https://pear.php.net/package/PHP_CodeSniffer) (`phpcs`) are in your `PATH`.

## Integration Testing

Once the code is deployed, the integration tests can be run.

Running the integration tests requires phpunit 6.5.14 and the curl command.

**For more information on the integration tests and the test data format, consult the tests/integration/integration-test-description.html and tests/integration/how-to-read-output.txt files.  Detailed test output is generated in the build/test-output directory once the integration tests are run.**

To run integration tests, execute the following script from the root of the repository:

```
$ ./run_default_tests.sh
```

## Code compliance verification

Running the code compliance requires phpcs. If you installed the development dependencies using ``composer`` then type the following from the root of the repository see if the code complies with MediaWiki's coding conventions:

```
$ phpcs --standard=vendor/mediawiki/mediawiki-codesniffer/MediaWiki Memento
```
