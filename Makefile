# This file is part of the Memento Extension to MediaWiki
# https://www.mediawiki.org/wiki/Extension:Memento
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
TESTOUTPUTDIRNAME=test-output

# the resulting packaged binary
BINFILE=Memento.zip

# file for storing test output so it can be shared for review
TESTOUTPUTFILE=Memento-test-output.zip

# commands and variables used for deployment/undeployment
ZIPCMD=zip -r
RM=rm
DEPLOYDIR=${MWDIR}/extensions
MWCONF=${MWDIR}/LocalSettings.php
UNZIPCMD=unzip -o -d ${DEPLOYDIR}
TESTINCLUDEPATH="`pwd`/Memento:`pwd`/tests/lib"
STARTINGDIR=`pwd`
TESTOUTPUTDIR=${BUILDDIR}/${TESTOUTPUTDIRNAME}

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
	-mkdir -p "${BUILDDIR}"
	@echo "Done with preparation"
	@echo "#########################"
	@echo ""

# create the test output directory
${TESTOUTPUTDIR}:
	@echo ""
	@echo "#########################"
	@echo "Preparing test output directory '${TESTOUTPUTDIR}'"
	-mkdir -p "${TESTOUTPUTDIR}"
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
	-${RM} -rf ${TESTOUTPUT}
	-${RM} -rf ${BUILDDIR}
	@echo "Done cleaning..."
	@echo "#########################"
	@echo ""

# run the unit tests
# Note:  requires that phpunit be installed
# Unit tests fell out of use in favor of integration tests
#unit-test:
#	@echo ""
#	@echo "#########################"
#	phpunit --include-path "${TESTINCLUDEPATH}" tests/unit
#	@echo "#########################"
#	@echo ""


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
	find ${DEPLOYDIR}/Memento -type d -exec chmod 0755 {} \; 
	find ${DEPLOYDIR}/Memento -type f -exec chmod 0644 {} \; 
	@echo "Deployment complete"
	@echo "#########################"
	@echo ""

# undeploy the packaged software, requires that it be deployed
undeploy: check-deploy-env ${DEPLOYDIR}/Memento
	@echo ""
	@echo "#########################"
	@echo "Removing deployed Memento extension"
	${RM} -rf ${DEPLOYDIR}/Memento
	sed -i "" -e '/require_once "$$IP\/extensions\/Memento\/Memento.php";/d' ${MWCONF}
	sed -i "" -e '/$$wgMementoTimemapNumberOfMementos = 3;/d' ${MWCONF}
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

defaults-integration-test: standard-integration-test 302-style-time-negotiation-recommended-headers-integration-test friendly-error-integration-test timemap-integration-test

# run tests on all non-configurable items
standard-integration-test: check-integration-env ${TESTOUTPUTDIR}
	@echo "standard-integration-test"
	@echo ""
	@echo "#########################"
	@echo "Running standard integration tests that apply in all cases"
	cd ${TESTOUTPUTDIR}; phpunit --include-path "${STARTINGDIR}/../../Memento:${STARTINGDIR}/../../tests/lib:${TESTDATADIR}" --group all "${STARTINGDIR}/../../tests/integration"
	@echo "Done with integration tests"
	@echo "#########################"
	@echo ""

# run all of the tests using 302-style time negotiation and recommended headers
302-style-time-negotiation-recommended-headers-integration-test: check-integration-env ${TESTOUTPUTDIR}
	@echo "302-style-time-negotiation-recommended-headers-integration-test"
	@echo ""
	@echo "#########################"
	@echo "Running 302-style time negotiation integration with recommended headers tests"
	cd ${TESTOUTPUTDIR}; phpunit --include-path "${STARTINGDIR}/../../Memento:${STARTINGDIR}/../../tests/lib:${TESTDATADIR}" --group 302-style-recommended-headers "${STARTINGDIR}/../../tests/integration"
	@echo "Done with integration tests"
	@echo "#########################"
	@echo ""

# run all of the tests on timemaps
timemap-integration-test: check-integration-env ${TESTOUTPUTDIR}
	@echo "timemap-integration-test"
	@echo ""
	@echo "#########################"
	@echo "Running timemap integration tests"
	cd ${TESTOUTPUTDIR}; phpunit --include-path "${STARTINGDIR}/../../Memento:${STARTINGDIR}/../../tests/lib:${TESTDATADIR}" --group timemap "${STARTINGDIR}/../../tests/integration"
	@echo "Done with integration tests"
	@echo "#########################"
	@echo ""


# run all of the friendly error integration tests
friendly-error-integration-test: check-integration-env ${TESTOUTPUTDIR}
	@echo "friendly-error-integration-test"
	@echo ""
	@echo "#########################"
	@echo "Running friendly error integration tests"
	cd ${TESTOUTPUTDIR}; phpunit --include-path "${STARTINGDIR}/../../Memento:${STARTINGDIR}/../../tests/lib:${TESTDATADIR}" --group friendlyErrorPages "${STARTINGDIR}/../../tests/integration"
	@echo "Done with integration tests"
	@echo "#########################"
	@echo ""

anonymize-test-output: ${TESTOUTPUTDIR}
	@echo "anonymize-test-output"
	@echo ""
	@echo "#########################"
	@echo "Anonymizing sensitive data"
	sed -i '' -e 's/> Cookie:.*/> Cookie: ANONYMIZED/g' ${TESTOUTPUTDIR}/*-debug.txt
	@echo "Done with anonymization"
	@echo "#########################"
	@echo ""

# packaging the output test for external review and analysis
package-test-output: ${TESTOUTPUTDIR} tests/integration/how-to-read-output.txt tests/integration/integration-test-description.html
	@echo ""
	@echo "#########################"
	@echo "Packaging test output"
	cp tests/integration/README ${TESTOUTPUTDIR}
	cp tests/integration/how-to-read-output.txt ${TESTOUTPUTDIR}
	cp tests/integration/integration-test-description.html ${TESTOUTPUTDIR}
	cd ${TESTOUTPUTDIR}/..; ${ZIPCMD} ${TESTOUTPUTFILE} ${TESTOUTPUTDIRNAME}
	@echo "#########################"
	@echo ""
	

check-integration-env:
	echo "Ensuring environment is set up correctly"
ifndef TESTDATADIR
	$(error TESTDATADIR is not defined, type 'export TESTDATADIR=<folder holding test data files>')
endif
ifndef TESTUSERNAME
	$(error TESTUSERNAME is not defined, type 'export TESTUSERNAME=<username of test user>; set it to NOAUTH to avoid authentication')
endif
ifndef TESTPASSWORD
	$(error TESTPASSWORD is not defined, type 'export TESTPASSWORD=<password of test user>; set it to NOAUTH to avoid authentication')
endif

# verify code against coding standards
verify:
	@echo ""
	@echo "#########################"
	@echo "Verifying against Mediawiki coding standards"
	phpcs --standard=externals/mediawiki-codesniffer/MediaWiki Memento
	@echo "#########################"
	@echo ""
