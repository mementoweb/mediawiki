The idea of the Memento extension is it to make it as straightforward to access articles of the past as it is to access their current version.

The Memento framework allows you to see versions of articles as they existed at some date in the past. All you need to do is enter a URL of an article in your browser and specify the desired date in a browser plug-in. This way you can browse the Web of the past. What the Memento extension will present to you is a version of the article as it existed on or very close to the selected date. Obviously, this will only work if previous (archived) versions are available on the Web. Fortunately, MediaWiki is a Content Management System which implies that it maintains all revisions made to an article. This extension leverages this archiving functionality and provides native Memento support for MediaWiki.

This package contains the source code, build scripts, and tests for the Memento MediaWiki Extension.

This file also contains installation information, but more comprehensive information about the extension is at: https://www.mediawiki.org/wiki/Extension:Memento

Note: the released version of this extension does not contain this ``README.md`` file, so the target audience for this file is those who wish to build/maintain the source code.

# Installation

To install this package within MediaWiki perform the following:
* copy the ``Memento`` directory into the extensions directory of your MediaWiki installation
* add the following to the LocalSettings.php file in your MediaWiki installation:
```
    wfLoadExtension( 'Memento' );
```

# Configuration

This extension has sensible defaults, but also allows the following settings to be added to LocalSettings.php in order to alter its behavior:

* `$wgMementoTimemapNumberOfMementos` - (default is 500) allows the user to alter the number of Mementos included in a TimeMap served up by this extension (default is 500)

* `$wgMementoIncludeNamespaces` - is an array of MediaWiki Namespace IDs (e.g. the integer values for Talk, Template, etc.) to include for Mementofication, default is an array containing just 0 (Main); the list of MediaWiki Namespace IDs is at https://www.mediawiki.org/wiki/Manual:Namespace

# Packaging

To package the Memento MediaWiki Extension, type the following 
from this directory:

    make package

This serves to run everything needed to verify the code and package the zip for release.

# Automated Deployment and Testing

## Using Docker

Easier testing with supported MediaWiki versions is now available via Docker. First change into the directory containing the docker-compose files:

```
cd tests/docker-image
```


Then decide which version of MediaWiki to test against. We currently test against:
* 1.31.1
* 1.32.1

Run the following to start the container for 1.31.1:

```
./starttestdocker.sh 1.31.1
```

**Do not forget this step!** Run the following to load the database:

```
docker exec docker-image_database_1 /bin/bash -c /loaddb.sh
```

This extra manual step is necessary because the script does not yet know when the database has fully started.

Change back to the directory at the top of the repository:

```
cd ../../
```

Run the tests as stated in the **Integration Testing** section.


## Using your own MediaWiki Installation

To deploy the Memento MediaWiki Extension locally for testing, one must first indicate to the shell where MediaWiki is installed, then run the appropriate make target.

```
    export MWDIR=<where your MediaWiki is installed>
    make deploy
```

To remove the software from a MediaWiki instance, type:

```
    make undeploy
```

# Setting Up Testing and Code Compliance

If you have [composer](https://getcomposer.org/) installed, you can install this version of PHPUnit and PHP Code Sniffer by running:

```
php /path/to/composer install

export PATH=$PATH:`pwd`/vendor/bin
```

If do not have [composer](https://getcomposer.org/), you will need to ensure that [PHP Unit](https://phpunit.de/) (``phpunit``) and [PHP Code Sniffer](https://pear.php.net/package/PHP_CodeSniffer) (``phpcs``) are in your ``PATH``.

# Integration Testing

Once the code is deployed, the integration tests can be run.

Running the integration tests requires phpunit 6.5.14 and the curl command.

**For more information on the integration tests and the test data format, consult the tests/integration/integration-test-description.html and tests/integration/how-to-read-output.txt files.  Detailed test output is generated in the build/test-output directory once the integration tests are run.**

To run integration tests, execute the following script from the root of the repository:

```
./run_default_tests.sh
```

# Code compliance verification

Running the code compliance requires phpcs. If you installed the development dependencies using ``composer`` then type the following to see if the code complies with MediaWiki's coding conventions:

```
    phpcs --standard=vendor/mediawiki/mediawiki-codesniffer/MediaWiki Memento
```

