#!/bin/sh

export MWDIR=$1

echo "Testing Memento Extension in MediaWiki installation at ${MWDIR}"

export TESTDATADIR=`pwd`/tests/data/local-demo-wiki
export TESTUSERNAME=NOAUTH
export TESTPASSWORD=NOAUTH

make undeploy 
make clean package deploy
make defaults-integration-test
