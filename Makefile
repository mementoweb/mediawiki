BUILDDIR=build
BINFILE=memento.zip
ZIPCMD=zip -r
RM=rm

# create the package
all: package
	@echo "Done with build"

prepare:
	@echo "Preparing build directory ${BUILDDIR}"
	mkdir ${BUILDDIR}
	@echo "Done with preparation"

package: prepare
	@echo "Creating package"
	${ZIPCMD} ${BUILDDIR}/${BINFILE} memento	
	@echo "Packaging complete"

clean:
	@echo "Cleaning up"
	-${RM} -rf ${BUILDDIR}
	@echo "Done cleaning..."
