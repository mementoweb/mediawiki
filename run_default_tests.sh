#!/bin/sh

export TESTDATADIR=`pwd`/tests/data/local-demo-wiki
export TESTUSERNAME=NOAUTH
export TESTPASSWORD=NOAUTH

make defaults-integration-test
