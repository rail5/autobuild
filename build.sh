#!/bin/bash

echo "NOTICE:"
echo ""
echo "In order to run this script, you need:"
echo " - all the buildtools (GCC, Lazarus, etc) necessary to build BookThief+Liesel (incl. dependencies)"
echo " - MXE, with GCC11 configured for x86_64 in your \$PATH"
echo " - Inno Setup 6 via Wine (configure location in this script)"
## Location for Inno is circa Line 179, beginning "wine "
echo " - Git installed to download sources"
echo " - devscripts"

while true; do
	read -p "Do you want to go ahead (y/n)" yn
	case $yn in
		[Yy]* ) echo "Alright then"; break;;
		[Nn]* ) exit 1; break;;
		* ) echo "Answer yes or no";;
	esac
done


echo "----"
echo "----"
echo "Let's build BookThief+Liesel"
echo "----"
echo "----"

initdir=`pwd`

nowvar=`date +%Y-%h-%d-%H%M%S`

mkdir -p $nowvar

cd $nowvar

git clone https://github.com/rail5/liesel.git

git clone https://github.com/rail5/bookthief.git

mkdir -p srconly/liesel
mkdir -p srconly/bookthief

mkdir -p deb/liesel
mkdir -p deb/bookthief

mkdir -p win/build/liesel
mkdir -p win/build/bookthief
mkdir -p win/release/source/source/liesel
mkdir -p win/release/source/source/bookthief
mkdir -p win/release/pkg/

mkdir -p release

mv ./bookthief/* srconly/bookthief/
mv ./liesel/* srconly/liesel/

rm -rf ./bookthief/
rm -rf ./liesel/

cp -rv srconly/liesel/* deb/liesel/
cp -rv srconly/liesel/* win/build/liesel/
cp -rv srconly/liesel/* win/release/source/source/liesel/

cp -rv srconly/bookthief/* deb/bookthief/
cp -rv srconly/bookthief/* win/build/bookthief/
cp -rv srconly/bookthief/* win/release/source/source/bookthief/

echo "All source files copied"

buildingdebbinary=0
echo "---"
while true; do
	read -p "Do you want to build .DEB binary packages for Liesel+BookThief? (y/n) " yn
	case $yn in
		[Yy]* ) buildingdebbinary=1; break;;
		[Nn]* ) buildingdebbinary=0; break;;
		* ) echo "Answer yes or no";;
	esac
done

btversion=0
lieselversion=0

if [[ buildingdebbinary -eq 1 ]]; then

	echo "--"
	echo "Building DEB packages"
	echo "--"

	echo "----"
	echo "Building Liesel DEB package"
	echo "----"

	cd deb/liesel

	debuild -us -uc
	if [ $? -eq 0 ]; then
		echo "Liesel successfully built"
	else
		echo "Liesel build failed -- check your source"
		exit 1
	fi
	
	echo "----"
	echo "Building BookThief DEB package"
	echo "----"
	
	cd ../bookthief
	
	debuild -us -uc
	if [ $? -eq 0 ]; then
		echo "BookThief successfully built"
	else
		echo "BookThief build failed -- check your source"
		exit 1
	fi

	echo "----"
	echo "Moving AMD64 DEB packages to release folder"
	echo "----"

	mv ../*.deb $initdir/$nowvar/release/

	versionsearch=`ls .. | grep bookthief_ | grep .dsc`
	btversion="${versionsearch/bookthief_/}"
	btversion="${btversion/.dsc/}"
	echo "Discovered bookthief version:"
	echo $btversion

	lieselversionsearch=`ls .. | grep liesel_ | grep .dsc`
	lieselversion="${lieselversionsearch/liesel_/}"
	lieselversion="${lieselversion/.dsc/}"
	echo "Discovered liesel version:"
	echo $lieselversion

	echo "--"
	echo "--"
	echo "DEB packages built"
	echo "--"
	echo "--"
	echo "----"
fi

buildingwin64=0
echo "---"
while true; do
	read -p "Do you want to build the Win64 Installer package? (y/n) " yn
	case $yn in
		[Yy]* ) buildingwin64=1; break;;
		[Nn]* ) buildingwin64=0; break;;
		* ) echo "Answer yes or no";;
	esac
done

if [[ buildingwin64 -eq 1 ]]; then
	if [[ btversion -eq 0 ]]; then
		read -p "Please input the current BookThief version number: " btversion
	fi
	
	echo "Building BT $btversion for Windows"

	echo "Cross-compiling Win64 binaries"
	echo "----"

	cd $initdir/$nowvar/win/build/liesel

	make windows CROSS=x86_64-w64-mingw32.static-
	if [ $? -eq 0 ]; then
		echo "Liesel-win64 successfully built"
	else
		echo "Liesel-win64 build failed -- check your source"
		exit 1
	fi

	mv ./liesel.exe ../../release/pkg/
	cp ./LICENSE ../../release/pkg/LICENSE.txt

	cd ../bookthief

	make windows
	if [ $? -eq 0 ]; then
		echo "BookThief-win64 successfully built"
	else
		echo "BookThief-win64 build failed -- check your source"
		exit 1
	fi

	mv ./bookthief.exe ../../release/pkg/

	echo "----"
	echo "----"
	echo "Building Win64 Installer Package"
	echo "----"
	echo "----"
	
	cd ../../release/
	
	echo "--"
	echo "--"
	echo "Creating Installer script"
	echo "--"
	echo "--"
	
	php $initdir/autobuild.php -b -v $btversion -p `pwd` > ./bt-$btversion.iss
	
	echo "--"
	echo "--"
	echo "Compiling Installer script"
	echo "--"
	echo "--"
	
	wine "/root/.wine/drive_c/Program Files/Inno Setup 6/ISCC.exe" ./bt-$btversion.iss
	if [ $? -eq 0 ]; then
		echo "Win64 Installer successfully built"
	else
		echo "Win64 Installer build failed -- check your source"
		exit 1
	fi

	echo "----"
	echo "Moving Win64 Installer to release folder"
	echo "----"
	
	mv ./pkg/BookThief-$btversion-Installer.exe $initdir/$nowvar/release/
	

	echo "----"
	echo "----"
	echo "----"
	echo "----"
fi

buildingdebsrc=0
echo "---"
while true; do
	read -p "Do you want to build the .DEB source-only package? (y/n) " yn
	case $yn in
		[Yy]* ) buildingdebsrc=1; break;;
		[Nn]* ) buildingdebsrc=0; break;;
		* ) echo "Answer yes or no";;
	esac
done

if [[ buildingdebsrc -eq 1 ]]; then

	if [[ btversion -eq 0 ]]; then
		read -p "Please input the current BookThief version number: " btversion
	fi
	
	if [[ lieselversion -eq 0 ]]; then
		read -p "Please input the current Liesel version number: " lieselversion
	fi

	echo "ALL BINARIES PRODUCED AND IN RELEASE FOLDER"
	echo "Now pushing packages"
	echo "----"
	echo "----"
	echo "----"
	echo "----"


	echo "--"
	echo "--"
	echo "Building source-only packages for LaunchPad"
	echo "--"
	echo "--"

	cd $initdir/$nowvar/srconly/liesel
	
	debuild -S -us -uc
	if [ $? -eq 0 ]; then
		echo "Liesel-SRC successfully built"
	else
		echo "Liesel-SRC build failed -- check your source"
		exit 1
	fi
	
	cd ../bookthief

	debuild -S -us -uc
	if [ $? -eq 0 ]; then
		echo "BookThief-SRC successfully built"
	else
		echo "BookThief-SRC build failed -- check your source"
		exit 1
	fi

	cd ..

	echo "--"
	echo "--"
	echo "Signing source packages"
	echo "--"
	echo "--"

	lsrcfile="liesel_$lieselversion"
	lsrcend="_source.changes"

	debsign ./$lsrcfile$lsrcend

	btsrcfile="bookthief_$btversion"

	debsign ./$btsrcfile$lsrcend


	echo "--"
	echo "--"
	echo "Source packages signed"
	echo "Ready to push"
	echo "--"
	echo "--"

	echo "--"
	echo "--"
	echo "Pushing to Launchpad PPA"
	echo "--"
	echo "--"


	while true; do
		read -p "Do you want to push Liesel $lieselversion to the PPA? (y/n)" yn
		case $yn in
			[Yy]* ) echo "PUSHING"; dput ppa:rail5/bookthief ./$lsrcfile$lsrcend; break;;
			[Nn]* ) echo "NOT pushing"; break;;
			* ) echo "Answer yes or no";;
		esac
	done
	
	while true; do
		read -p "Do you want to push BookThief $btversion to the PPA? (y/n)" yn
		case $yn in
			[Yy]* ) echo "PUSHING"; dput ppa:rail5/bookthief ./$btsrcfile$lsrcend; break;;
			[Nn]* ) echo "NOT pushing"; break;;
			* ) echo "Answer yes or no";;
		esac
	done
	
fi

exit 0
	## Code below is not implemented and probably won't be but who knows

pushtosite=0

lieseltosite=0
bttosite=0
btwin64tosite=0


while true; do
	read -p "Do you want to push anything to rail5.org? (y/n)" yn
	case $yn in
		[Yy]* ) pushtosite=1; break;;
		[Nn]* ) echo "NOT pushing"; break;;
		* ) echo "Answer yes or no";;
	esac
done

if [[ $pushtosite -eq 1 ]]; then

	while true; do
		read -p "Push Liesel $lieselversion DEB package to rail5.org? (y/n)" yn
		case $yn in
			[Yy]* ) lieseltosite=1; break;;
			[Nn]* ) echo "NOT pushing"; break;;
			* ) echo "Answer yes or no";;
		esac
	done

	while true; do
		read -p "Push BookThief $btversion DEB package to rail5.org? (y/n)" yn
		case $yn in
			[Yy]* ) bttosite=1; break;;
			[Nn]* ) echo "NOT pushing"; break;;
			* ) echo "Answer yes or no";;
		esac
	done

	while true; do
		read -p "Push BookThief $btversion WIN64 INSTALLER to rail5.org? (y/n)" yn
		case $yn in
			[Yy]* ) btwin64tosite=1; break;;
			[Nn]* ) echo "NOT pushing"; break;;
			* ) echo "Answer yes or no";;
		esac
	done
	
	## Pushing
	
	echo "pushing requested to rail5.org"
	echo ""
	echo "Liesel: $lieseltosite"
	echo "BookThief: $bttosite"
	echo "BT-Win64: $btwin64tosite"
	
fi






while true; do
	read -p "Do you want to make GitLab/GitHub Release pages for Liesel $lieselversion? (y/n)" yn
	case $yn in
		[Yy]* ) echo "PUSHING"; break;;
		[Nn]* ) echo "NOT pushing"; break;;
		* ) echo "Answer yes or no";;
	esac
done

while true; do
	read -p "Do you want to make GitLab/GitHub Release pages for BookThief $btversion? (y/n)" yn
	case $yn in
		[Yy]* ) echo "PUSHING"; break;;
		[Nn]* ) echo "NOT pushing"; break;;
		* ) echo "Answer yes or no";;
	esac
done
