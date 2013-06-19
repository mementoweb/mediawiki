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
	@echo "Preparing build directory ${BUILDDIR}"
	-mkdir ${BUILDDIR}
	@echo "Done with preparation"

# create ZIP file used for release
package: ${BUILDDIR}
	@echo "Creating package"
	${ZIPCMD} ${BUILDDIR}/${BINFILE} memento	
	@echo "Packaging complete"

# clean up
clean:
	@echo "Cleaning up"
	-${RM} -rf ${BUILDDIR}
	@echo "Done cleaning..."


# OPTIONAL DEPLOY SECTION
#
# Everything below here is used to deploy the existing packaged application
# during development.  The goal is to allow changes to be made to the code,
# then rapid deployment for integration testing.
#
# Pre-requesites:  export MWDIR=<your Mediawiki installation directory>
#

# deploy the packaged software, requires a "make package" to be run first
deploy: ${BUILDDIR}/${BINFILE} check-env
	@echo "Deploying Memento extension"
	${UNZIPCMD} ${BUILDDIR}/${BINFILE} 
	echo 'require_once "$$IP/extensions/memento/memento.php";' >> ${MWCONF}
	echo '$$wgArticlePath="$$wgScriptPath/index.php/$$1";' >> ${MWCONF}
	echo '$$wgUsePathInfo = true;' >> ${MWCONF}
	find ${DEPLOYDIR}/memento -type d -exec chmod 0755 {} \; 
	find ${DEPLOYDIR}/memento -type f -exec chmod 0644 {} \; 
	@echo "Deployment complete"

# undeploy the packaged software, requires that it be deployed
undeploy: ${DEPLOYDIR}/memento check-env
	@echo "Removing deployed memento extension"
	${RM} -rf ${DEPLOYDIR}/memento
	sed -i "" -e '/require_once "$$IP\/extensions\/memento\/memento.php";/d' ${MWCONF}
	sed -i "" -e '/$$wgArticlePath="$$wgScriptPath\/index.php\/$$1";/d' ${MWCONF}
	sed -i "" -e '/$$wgUsePathInfo = true;/d' ${MWCONF}
	@echo "Removal complete"

# check that MWDIR is set in the environment, fail with error message if not
check-env:
ifndef MWDIR
	$(error MWDIR is not defined, type 'export MWDIR=<your Mediawiki installation directory>')
endif
