# This file is part of the Memento Extension to MediaWiki
# http://www.mediawiki.org/wiki/Extension:Memento
#
# LICENSE
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
# http://www.gnu.org/copyleft/gpl.html
# 
# Makefile

# Settings
BUILDDIR=build

# the resulting packaged binary
BINFILE=Memento.zip

# commands and variables used for deployment/undeployment
ZIPCMD=zip -r
RM=rm
DEPLOYDIR=${MWDIR}/extensions
MWCONF=${MWDIR}/LocalSettings.php
UNZIPCMD=unzip -o -d ${DEPLOYDIR}

.PHONY: clean
.PHONY: unit-test
.PHONY: alter-installation-traditional-errors
.PHONY: alter-installation-friendly-errors
.PHONY: alter-installation-time-negotiation
.PHONY: alter-installation-no-time-negotiation
.PHONY: check-integration-env
.PHONY: verify

# default target
all: package
	@echo "Done with build"

# create the build directory
${BUILDDIR}:
	@echo ""
	@echo "#########################"
	@echo "Preparing build directory '${BUILDDIR}'"
	-mkdir ${BUILDDIR}
	@echo "Done with preparation"
	@echo "#########################"
	@echo ""

# create ZIP file used for release
package: ${BUILDDIR}
	@echo ""
	@echo "#########################"
	@echo "Creating package"
	${ZIPCMD} ${BUILDDIR}/${BINFILE} Memento	
	@echo "Packaging complete"
	@echo "#########################"
	@echo ""

# clean up
clean:
	@echo ""
	@echo "#########################"
	@echo "Cleaning up"
	-${RM} -rf ${BUILDDIR}
	@echo "Done cleaning..."
	@echo "#########################"
	@echo ""

# run the unit tests
# Note:  requires that phpunit be installed
unit-test:
	@echo ""
	@echo "#########################"
	phpunit --include-path "Memento:tests/lib" tests/unit
	@echo "#########################"
	@echo ""


# DEPLOY AND INTEGRATION TEST SECTION
#
# Everything below here is used to deploy the existing packaged application
# during development.  The goal is to allow changes to be made to the code,
# then rapid deployment for integration testing.
#
# Pre-requisites:  export MWDIR=<your Mediawiki installation directory>
#

deploy: deploy-default

# deploy the packaged software, requires a "make package" to be run first
deploy-default: check-deploy-env ${BUILDDIR}/${BINFILE}
	@echo ""
	@echo "#########################"
	@echo "Deploying Memento extension"
	${UNZIPCMD} ${BUILDDIR}/${BINFILE} 
	echo 'require_once "$$IP/extensions/Memento/Memento.php";' >> ${MWCONF}
	echo '$$wgArticlePath="$$wgScriptPath/index.php/$$1";' >> ${MWCONF}
	echo '$$wgUsePathInfo = true;' >> ${MWCONF}
	echo '$$wgMementoTimemapNumberOfMementos = 3;' >> ${MWCONF}
	find ${DEPLOYDIR}/Memento -type d -exec chmod 0755 {} \; 
	find ${DEPLOYDIR}/Memento -type f -exec chmod 0644 {} \; 
	@echo "Deployment complete"
	@echo "#########################"
	@echo ""

alter-installation-traditional-errors:
	@echo ""
	@echo "#########################"
	@echo "Setting Traditional Error Page Type"
	sed -i "" -e '/$$wgMementoErrorPageType =.*;/d' ${MWCONF}	
	echo '$$wgMementoErrorPageType = "traditional";' >> ${MWCONF}
	@echo "#########################"
	@echo ""

alter-installation-friendly-errors:
	@echo ""
	@echo "#########################"
	@echo "Setting Friendly Error Page Type"
	sed -i "" -e '/$$wgMementoErrorPageType =.*;/d' ${MWCONF}	
	echo '$$wgMementoErrorPageType = "friendly";' >> ${MWCONF}
	@echo "#########################"
	@echo ""

alter-installation-time-negotiation:
	@echo ""
	@echo "#########################"
	@echo "Setting Time Negotiation"
	sed -i "" -e '/$$wgMementoTimeNegotiation =.*;/d' ${MWCONF}
	echo '$$wgMementoTimeNegotiation = true;' >> ${MWCONF}
	@echo "#########################"
	@echo ""

alter-installation-no-time-negotiation:
	@echo ""
	@echo "#########################"
	@echo "Setting Time Negotiation"
	sed -i "" -e '/$$wgMementoTimeNegotiation =.*;/d' ${MWCONF}
	echo '$$wgMementoTimeNegotiation = false;' >> ${MWCONF}
	@echo "#########################"
	@echo ""

# undeploy the packaged software, requires that it be deployed
undeploy: check-deploy-env ${DEPLOYDIR}/Memento
	@echo ""
	@echo "#########################"
	@echo "Removing deployed Memento extension"
	${RM} -rf ${DEPLOYDIR}/Memento
	sed -i "" -e '/require_once "$$IP\/extensions\/Memento\/Memento.php";/d' ${MWCONF}
	sed -i "" -e '/$$wgArticlePath="$$wgScriptPath\/index.php\/$$1";/d' ${MWCONF}
	sed -i "" -e '/$$wgUsePathInfo = true;/d' ${MWCONF}
	sed -i "" -e '/$$wgMementoTimemapNumberOfMementos = 3;/d' ${MWCONF}
	sed -i "" -e '/$$wgMementoErrorPageType =.*;/d' ${MWCONF}
	sed -i "" -e '/$$wgMementoTimeNegotiation =.*;/d' ${MWCONF}
	@echo "Removal complete"
	@echo "#########################"
	@echo ""

# check that MWDIR is set in the environment, fail with error message if not
check-deploy-env:
ifndef MWDIR
	$(error MWDIR is not defined, type 'export MWDIR=<your Mediawiki installation directory>')
endif

# INTEGRATION TEST SECTION
#
# Pre-requisites:  export TESTHOST=<hostname of the host under test>
#

integration-test: deploy-default alter-installation-traditional-errors standard-integration-test traditional-error-integration-test alter-installation-friendly-errors standard-integration-test friendly-error-integration-test alter-installation-time-negotiation time-negotiation-integration-test undeploy

standard-integration-test: check-integration-env
	@echo ""
	@echo "#########################"
	@echo "Running standard integration tests that apply in all cases"
	-phpunit --include-path "Memento:tests/lib" --group all tests/integration
	@echo "Done with integration tests"
	@echo "#########################"
	@echo ""


# run all of the friendly error integration tests
friendly-error-integration-test: check-integration-env
	@echo ""
	@echo "#########################"
	@echo "Running friendly error integration tests"
	-phpunit --include-path "Memento:tests/lib" --group friendlyErrorPages tests/integration
	@echo "Done with integration tests"
	@echo "#########################"
	@echo ""

# run all of the traditional error integration tests
traditional-error-integration-test: check-integration-env
	@echo ""
	@echo "#########################"
	@echo "Running traditional error integration tests"
	-phpunit --include-path "Memento:tests/lib" --group traditionalErrorPages tests/integration
	@echo "Done with integration tests"
	@echo "#########################"
	@echo ""

# run all of the tests using 200-style time negotiation
time-negotiation-integration-test: check-integration-env
	@echo ""
	@echo "#########################"
	@echo "Running time negotiation integration tests"
	-phpunit --include-path "Memento:tests/lib" --group timeNegotiation tests/integration
	@echo "Done with integration tests"
	@echo "#########################"
	@echo ""

check-integration-env:
ifndef TESTHOST
	$(error TESTHOST is not defined, type 'export TESTHOST=<host to test>')
endif

# verify code against coding standards
verify:
	@echo ""
	@echo "#########################"
	@echo "Verifying against Mediawiki coding standards"
	phpcs --standard=externals/mediawiki-codesniffer/MediaWiki Memento
	@echo "#########################"
	@echo ""
