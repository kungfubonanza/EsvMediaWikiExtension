#! /bin/bash

#####
# This file is part of the MediaWiki extension Esv.
#
# MIT License
#
# Copyright (c) 2015-2020 Kungfubonanza
#        
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#                               
# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.
#                                
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE. 
#
# @author Kungfubonanza
# @file
# @ingroup Esv
#####

set -x  # display commands and their expanded arguments
set -u  # treat unset variables as an error when performing parameter expansion
set -o pipefail  # pipelines exit with last (rightmost) non-zero exit code
set -e  # exit immediately if a command exits with an error

originalDirectory=$(pwd)

function installMediaWiki {
	cd ..

	wget https://github.com/wikimedia/mediawiki/archive/$MW.tar.gz
	tar -zxf $MW.tar.gz
	mv mediawiki-$MW mw

	cd mw

	composer install --prefer-source

	case "$DBTYPE" in
	"mysql")

		mysql -e 'create database its_a_mw;'
		php maintenance/install.php --dbtype $DBTYPE --dbuser root --dbname its_a_mw --pass nyan --scriptpath /TravisWiki TravisWiki admin

		;;

	"postgres")

		# See https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/458
		sudo /etc/init.d/postgresql stop

		# Travis@support: Try adding a sleep of a few seconds between starting PostgreSQL
		# and the first command that accesses PostgreSQL
		sleep 3

		sudo /etc/init.d/postgresql start
		sleep 3

		psql -c 'create database its_a_mw;' -U postgres
		php maintenance/install.php --dbtype $DBTYPE --dbuser postgres --dbname its_a_mw --pass nyan --scriptpath /TravisWiki TravisWiki admin

		;;

	"sqlite")

		php maintenance/install.php --dbtype $DBTYPE --dbuser root  --dbname its_a_mw --dbpath $(pwd) --pass nyan --scriptpath /TravisWiki TravisWiki admin

		;;

	*)
		echo "$DBTYPE is not a recognized database type."
		exit 1

	esac

}

function installExtensionViaComposerOnMediaWikiRoot {

	# fix setup for older MW versions and install extension
	#composer require --prefer-source --dev --update-with-dependencies "phpunit/phpunit:~4.0" "mediawiki/lingo:dev-master"
	git clone https://github.com/kungfubonanza/EsvMediaWikiExtension.git

	mv EsvMediaWikiExtension extensions/Esv
	cd extensions/Esv

	## Pull request number, "false" if it's not a pull request
	#if [ "$TRAVIS_PULL_REQUEST" != "false" ]
	#then
	#	git fetch origin +refs/pull/"$TRAVIS_PULL_REQUEST"/merge:
	#	git checkout -f FETCH_HEAD
	#else
	#	git fetch origin "$TRAVIS_BRANCH"
	#	git checkout -f FETCH_HEAD
	#fi

	git log HEAD^..HEAD

	cd ../..

	# Rebuild the class map after git fetch
	#composer dump-autoload

	echo 'error_reporting(E_ALL| E_STRICT);' >> LocalSettings.php
	echo 'ini_set("display_errors", 1);' >> LocalSettings.php
	echo '$wgShowExceptionDetails = true;' >> LocalSettings.php
	echo '$wgDevelopmentWarnings = true;' >> LocalSettings.php
	echo "putenv( 'MW_INSTALL_PATH=$(pwd)' );" >> LocalSettings.php
	echo "wfLoadExtension('Esv');" >> LocalSettings.php

	php maintenance/update.php --quick
}

#function uploadCoverageReport {
#	wget https://scrutinizer-ci.com/ocular.phar
#	php ocular.phar code-coverage:upload --repository='g/wikimedia/mediawiki-extensions-lingo' --format=php-clover coverage.clover
#}

#composer self-update

installMediaWiki
installExtensionViaComposerOnMediaWikiRoot

#cd extensions/Esv

#if [ "$MW" == "master" ]
#then
#	php ../../tests/phpunit/phpunit.php --group $GROUP -c phpunit.xml.dist --coverage-clover=coverage.clover
#
#	set +e
#	uploadCoverageReport
#else
#	php ../../tests/phpunit/phpunit.php --group $GROUP -c phpunit.xml.dist
#fi
