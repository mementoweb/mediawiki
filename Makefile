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
BINFILE=memento.zip

# commands and variables used for deployment/undeployment
ZIPCMD=zip -r
RM=rm
DEPLOYDIR=${MWDIR}/extensions
MWCONF=${MWDIR}/LocalSettings.php
UNZIPCMD=unzip -o -d ${DEPLOYDIR}

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
	${ZIPCMD} ${BUILDDIR}/${BINFILE} memento	
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


# DEPLOY AND INTEGRATION TEST SECTION
#
# Everything below here is used to deploy the existing packaged application
# during development.  The goal is to allow changes to be made to the code,
# then rapid deployment for integration testing.
#
# Pre-requisites:  export MWDIR=<your Mediawiki installation directory>
#

# deploy the packaged software, requires a "make package" to be run first
deploy: check-deploy-env ${BUILDDIR}/${BINFILE}
	@echo ""
	@echo "#########################"
	@echo "Deploying Memento extension"
	${UNZIPCMD} ${BUILDDIR}/${BINFILE} 
	echo 'require_once "$$IP/extensions/memento/memento.php";' >> ${MWCONF}
	echo '$$wgArticlePath="$$wgScriptPath/index.php/$$1";' >> ${MWCONF}
	echo '$$wgUsePathInfo = true;' >> ${MWCONF}
	echo '$$wgMementoTimemapNumberOfMementos = 3;' >> ${MWCONF}
	find ${DEPLOYDIR}/memento -type d -exec chmod 0755 {} \; 
	find ${DEPLOYDIR}/memento -type f -exec chmod 0644 {} \; 
	@echo "Deployment complete"
	@echo "#########################"
	@echo ""

# undeploy the packaged software, requires that it be deployed
undeploy: check-deploy-env ${DEPLOYDIR}/memento
	@echo ""
	@echo "#########################"
	@echo "Removing deployed memento extension"
	${RM} -rf ${DEPLOYDIR}/memento
	sed -i "" -e '/require_once "$$IP\/extensions\/memento\/memento.php";/d' ${MWCONF}
	sed -i "" -e '/$$wgArticlePath="$$wgScriptPath\/index.php\/$$1";/d' ${MWCONF}
	sed -i "" -e '/$$wgUsePathInfo = true;/d' ${MWCONF}
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

# run all of the integration tests
integration-test: check-integration-env
	@echo ""
	@echo "#########################"
	@echo "Running integration tests"
	phpunit --include-path tests/lib tests/integration
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
	phpcs --standard=coding-standards/Mediawiki memento
	@echo "#########################"
	@echo ""
