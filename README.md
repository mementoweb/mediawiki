The idea of the Memento extension is it to make it as straightforward to access articles of the past as it is to access their current version.

The Memento framework allows you to see versions of articles as they existed at some date in the past. All you need to do is enter a URL of an article in your browser and specify the desired date in a browser plug-in. This way you can browse the Web of the past. What the Memento extension will present to you is a version of the article as it existed on or very close to the selected date. Obviously, this will only work if previous (archived) versions are available on the Web. Fortunately, MediaWiki is a Content Management System which implies that it maintains all revisions made to an article. This extension leverages this archiving functionality and provides native Memento support for MediaWiki.

This package contains the source code, build scripts, and tests for the Memento MediaWiki Extension.

This file also contains installation information, but more comprehensive information about the extension is at:  https://www.mediawiki.org/wiki/Extension:Memento

Note: the released version of this extension does not contain this file, so the target audience for this file is those who wish to build/maintain the source code.

# Directory Contents

* Makefile - the build script that does all of the magic
* README.md - this file
* TODO - list of items to address in the codebase
* Memento/ - the source code for this extension
* externals/ - git submodule linking to the code verification rules at https://gerrit.wikimedia.org/r/p/mediawiki/tools/codesniffer.git
* scripts/ - command line scripts used for testing the extension by hand
* tests/integration/ - the integration tests
* tests/lib/ - libraries needed by the tests
* tests/data/ - data used by the tests


# Installation

To install this package within MediaWiki perform the following:
* copy the Memento directory into the extensions directory of your MediaWiki installation
* add the following to the LocalSettings.php file in your MediaWiki installation:
```
    require_once "$IP/extensions/Memento/Memento.php";
```

# Configuration

This extension has sensible defaults, but also allows the following settings to be added to LocalSettings.php in order to alter its behavior:

* `$wgMementoTimemapNumberOfMementos` - (default is 500) allows the user to alter the number of Mementos included in a TimeMap served up by this extension (default is 500)

* `$wgMementoIncludeNamespaces` - is an array of MediaWiki Namespace IDs (e.g. the integer values for Talk, Template, etc.) to include for Mementofication, default is an array containing just 0 (Main); the list of MediaWiki Namespace IDs is at https://www.mediawiki.org/wiki/Manual:Namespace

* `$wgMementoTimeNegotiationForThumbnails` - EXPERIMENTAL: MediaWiki, by default, does not preserve temporal coherence for its oldid pages.  In other words, and oldid (URI-M) page will not contain the version of the image that existed when that page was created.  See http://arxiv.org/pdf/1402.0928.pdf for more information on this problem in web archives.
    * false - (default) do not attempt to match the old version of the image to the requested oldid page
    * true - attempt to match the old version of the image to the requested oldid page

# Packaging

To package the Memento MediaWiki Extension, type the following 
from this directory:

    make package

This serves to run everything needed to verify the code and package the zip for release.

# Automated Deployment for Testing

To deploy the Memento MediaWiki Extension locally for testing, one must first indicate to the shell where MediaWiki is installed, then run the appropriate make target.

```
    export MWDIR=<absolute path to where your MediaWiki is installed>
    make package deploy
```

To remove the software from a MediaWiki instance, type:

```
    make undeploy
```

# Integration Testing

Once the code is deployed, the integration tests can be run.

Running the integration tests requires:
* phpunit
* curl

To get set up (assuming you have cloned this repository):
1. Start with a new MediaWiki installation, with an empty database, and all default settings
2. Log in as the administrative user created during the installation
3. Go to Special:Import
4. Click "Choose File"
5. Select the file Kevan_Lannister.xml in the tests/data/demo-wiki-data directory of this repository
6. Choose "Import to default locations"
7. Click "Upload file"

You will need to change the test data inside tests/integration/data/local-demo-wiki to reflect your MediaWiki installation URIs and appropriate expected data.

**For more information on the integration tests and the test data format, consult the tests/integration/integration-test-description.html and tests/integration/how-to-read-output.txt files.  Detailed test output is generated in the build/test-output directory once the integration tests are run.**

Then execute:

````
    ./run_default_tests.sh <absolute path to where your MediaWiki is installed>
````

Test output is saved to build/test-output.

Of course, the fastest development process is:

1. edit tests or change code, if necessary
2. execute ./run_default_tests.sh as above

# Code compliance verification

Running the code compliance requires phpcs.

This git repository uses an external repository for coding convention rules, so we can update the coding convention rules at any time.  The git command for performing the initial import is:

```
    git submodule update --init
```

To see if the code complies with MediaWiki's coding conventions, run:

```
    make verify
```
