#!/bin/bash

echo "NOTICE:"
echo ""
echo "In order to run this script, you need:"
echo " - all the buildtools (GCC, Lazarus, etc) necessary to build BookThief+Liesel (incl. dependencies)"
echo " - (To publish to deb.rail5.org) reprepro"
echo " - (For win64 cross-compilation) MXE, with GCC11 configured for x86_64 in your \$PATH"
echo " - (For win64 cross-compilation) Inno Setup 6 via Wine (configure location in this script)"
echo " - Git installed to download sources"
echo " - devscripts"


# Configure Inno Setup location here (ISCC variable):
ISCC="$HOME/.wine/drive_c/Program Files (x86)/Inno Setup 6/ISCC.exe"

# Ubuntu distribution & Debian distribution names
ubuntudist="focal"
debiandist="bullseye"

# Directory structure:
#  - deb
#    Contains the source for the Debian packages (Binary+Source packages)
#    The packages here will be designated for a DEBIAN distribution ($debiandist)
#
#  - srconly
#    Contains the source for the Ubuntu Launchpad PPA packages (Source-only packages)
#    The packages here will be designated for a UBUNTU distribution ($ubuntudist)
#
#  - win
#    Contains the source for the Win64 cross-compilation
#
#  - release
#    All release binaries are moved to this directory after being built

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

mkdir srconly

cd srconly

git clone https://github.com/rail5/liesel.git

git clone https://github.com/rail5/bookthief.git

cd ..

mkdir -p deb/liesel
mkdir -p deb/bookthief

mkdir -p win/build/liesel
mkdir -p win/build/bookthief
mkdir -p win/release/source/source/liesel
mkdir -p win/release/source/source/bookthief
mkdir -p win/release/pkg/

mkdir -p release

cp -rv srconly/liesel/* deb/liesel/
# Swap all instances of $ubuntudist with $debiandist in the changelog for the deb/ folder
# Mark the package with a DEBIAN distribution
sed -i "s/$ubuntudist/$debiandist/gi" deb/liesel/debian/changelog

cp -rv srconly/liesel/* win/build/liesel/
cp -rv srconly/liesel/* win/release/source/source/liesel/

cp -rv srconly/bookthief/* deb/bookthief/
# Swap all instances of $ubuntudist with $debiandist in the changelog for the deb/ folder
# Mark the package with a DEBIAN distribution
sed -i "s/$ubuntudist/$debiandist/gi" deb/bookthief/debian/changelog

cp -rv srconly/bookthief/* win/build/bookthief/
cp -rv srconly/bookthief/* win/release/source/source/bookthief/

# Swap all instances of $debiandist with $ubuntudist in the changelog for the srconly/ folder
# Mark the packages with a UBUNTU distribution
sed -i "s/$debiandist/$ubuntudist/gi" srconly/liesel/debian/changelog
sed -i "s/$debiandist/$ubuntudist/gi" srconly/bookthief/debian/changelog

echo "All source files copied"

buildingdebbinary=0
echo "---"
while true; do
	read -p "Do you want to build .DEB packages for Liesel+BookThief? (y/n) " yn
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
	echo "Building Liesel DEB packages"
	echo "----"

	cd $initdir/$nowvar/deb/liesel

	debuild
	if [ $? -eq 0 ]; then
		echo "Liesel successfully built"
	else
		echo "Liesel build failed -- check your source"
		exit 1
	fi
	
	echo "----"
	echo "Building BookThief DEB packages"
	echo "----"
	
	cd $initdir/$nowvar/deb/bookthief
	
	debuild
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
	mv ../*.dsc $initdir/$nowvar/release/
	mv ../*.tar.gz $initdir/$nowvar/release/
	mv ../*.build $initdir/$nowvar/release/
	mv ../*.buildinfo $initdir/$nowvar/release/
	mv ../*.changes $initdir/$nowvar/release/

	versionsearch=`ls $initdir/$nowvar/release/ | grep bookthief_ | grep .dsc`
	btversion="${versionsearch/bookthief_/}"
	btversion="${btversion/.dsc/}"
	echo "Discovered bookthief version:"
	echo $btversion

	lieselversionsearch=`ls $initdir/$nowvar/release/ | grep liesel_ | grep .dsc`
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

buildfarmbuilds=0
echo "---"
while true; do
	read -p "Do you want to build other-architecture packages on the QEMU build farm? (y/n) " yn
	case $yn in
		[Yy]* ) buildfarmbuilds=1; break;;
		[Nn]* ) buildfarmbuilds=0; break;;
		* ) echo "Answer yes or no";;
	esac
done

if [[ buildfarmbuilds -eq 1 ]]; then
	cd $initdir
	./build-farm.sh
	debsign $initdir/build-farm/packages/*.changes
	mv $initdir/build-farm/packages/* $initdir/$nowvar/release/
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
	
	wine "$ISCC" ./bt-$btversion.iss
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
		read -p "Do you want to push Liesel $lieselversion to the Launchpad PPA? (y/n)" yn
		case $yn in
			[Yy]* ) echo "PUSHING"; dput ppa:rail5/bookthief ./$lsrcfile$lsrcend; break;;
			[Nn]* ) echo "NOT pushing"; break;;
			* ) echo "Answer yes or no";;
		esac
	done
	
	while true; do
		read -p "Do you want to push BookThief $btversion to the Launchpad PPA? (y/n)" yn
		case $yn in
			[Yy]* ) echo "PUSHING"; dput ppa:rail5/bookthief ./$btsrcfile$lsrcend; break;;
			[Nn]* ) echo "NOT pushing"; break;;
			* ) echo "Answer yes or no";;
		esac
	done
	
fi

echo "--"
echo "--"
echo "Pushing to deb.rail5.org Debian repo"
echo "--"
echo "--"

pushinglieseltodebrepo=0
pushingbttodebrepo=0
pushinganytodebrepo=0

lversionfile="liesel_$lieselversion"
btversionfile="bookthief_$btversion"
debchangesend="_amd64.changes"
debpkgend="_amd64.deb"

lchangesfile="$lversionfile$debchangesend"
btchangesfile="$btversionfile$debchangesend"

lpkgfile="$lversionfile$debpkgend"
btpkgfile="$btversionfile$debpkgend"

btwininstallerfile="BookThief-$btversion-Installer.exe"

while true; do
	read -p "Do you want to push Liesel $lieselversion to deb.rail5.org? (y/n)" yn
	case $yn in
		[Yy]* ) echo "SET TO PUSH"; pushinganytodebrepo=1; break;;
		[Nn]* ) echo "NOT pushing"; break;;
		* ) echo "Answer yes or no";;
	esac
done

while true; do
	read -p "Do you want to push BookThief $btversion to the deb.rail5.org? (y/n)" yn
	case $yn in
		[Yy]* ) echo "SET TO PUSH"; pushinganytodebrepo=1; break;;
		[Nn]* ) echo "NOT pushing"; break;;
		* ) echo "Answer yes or no";;
	esac
done

if [[ pushinganytodebrepo -eq 1 ]]; then
	cd $initdir/$nowvar
	git clone https://github.com/rail5/ppa.git
	cd ppa/debian
	
	reprepro -P optional include bullseye $initdir/$nowvar/release/*.changes
	
	cd $initdir/$nowvar/ppa
	git add --all
	git commit -m "Updated packages"
	
	git push origin
fi

# If we've built a Debian package & the Windows installer, we can push a Release page to GitHub
if [[ buildingwin64 -eq 1 ]] && [[ buildingdebbinary -eq 1 ]]; then

	createlieselrelease=0
	createbtrelease=0

	while true; do
		read -p "Do you want to make a GitHub Release page for Liesel $lieselversion? (y/n)" yn
		case $yn in
			[Yy]* ) echo "PUSHING"; createlieselrelease=1; break;;
			[Nn]* ) echo "NOT pushing"; break;;
			* ) echo "Answer yes or no";;
		esac
	done

	while true; do
		read -p "Do you want to make a GitHub Release page for BookThief $btversion? (y/n)" yn
		case $yn in
			[Yy]* ) echo "PUSHING"; createbtrelease=1; break;;
			[Nn]* ) echo "NOT pushing"; break;;
			* ) echo "Answer yes or no";;
		esac
	done
	
	
	if [[ createlieselrelease -eq 1 ]]; then
		changelog=$(php "$initdir/get-changelog.php" -d "$initdir/$nowvar" -l);
		
		cd $initdir/$nowvar/release
		
		OWNER=rail5
		REPOSITORY=liesel
		ACCESS_TOKEN=$(gpg -d /etc/git/github-token.gpg 2>/dev/null)
		
		# Create GitHub Release
		curl -L \
			-X POST \
			-H "Accept: application/vnd.github+json" \
			-H "Authorization: Bearer $ACCESS_TOKEN" \
			-H "X-GitHub-Api-Version: 2022-11-28" \
			https://api.github.com/repos/$OWNER/$REPOSITORY/releases \
			-d "{\"tag_name\": \"v$lieselversion\",
				\"target_commitish\": \"main\",
				\"name\": \"v$lieselversion\",
				\"body\": \"$changelog\",
				\"draft\": false,
				\"prerelease\": false,
				\"generate_release_notes\": false}" > liesel-release-info
		
		# Get GitHub Release ID
		RELEASEID=$(php "$initdir/get-release-id.php" -i "$initdir/$nowvar/release/liesel-release-info")
		
		# Upload liesel .DEB package
		curl -L \
			-X POST \
			-H "Accept: application/vnd.github+json" \
			-H "Authorization: Bearer $ACCESS_TOKEN" \
			-H "X-Github-Api-Version: 2022-11-28" \
			-H "Content-Type: application/octet-stream" \
			https://uploads.github.com/repos/$OWNER/$REPOSITORY/releases/$RELEASEID/assets?name=$lpkgfile \
			--data-binary "@$lpkgfile"
		
		cd $initdir/$nowvar
	fi
	
	if [[ createbtrelease -eq 1 ]]; then
		changelog=$(php "$initdir/get-changelog.php" -d "$initdir/$nowvar" -b);
		
		cd $initdir/$nowvar/release
		
		OWNER=rail5
		REPOSITORY=bookthief
		ACCESS_TOKEN=$(gpg -d /etc/git/github-token.gpg 2>/dev/null)
		
		# Create GitHub Release
		curl -L \
			-X POST \
			-H "Accept: application/vnd.github+json" \
			-H "Authorization: Bearer $ACCESS_TOKEN" \
			-H "X-GitHub-Api-Version: 2022-11-28" \
			https://api.github.com/repos/$OWNER/$REPOSITORY/releases \
			-d "{\"tag_name\": \"v$btversion\",
				\"target_commitish\": \"main\",
				\"name\": \"v$btversion\",
				\"body\": \"$changelog\",
				\"draft\": false,
				\"prerelease\": false,
				\"generate_release_notes\": false}" > bookthief-release-info
		# Get GitHub Release ID
		RELEASEID=$(php "$initdir/get-release-id.php" -i "$initdir/$nowvar/release/bookthief-release-info")
		# Upload liesel .DEB package
		curl -L \
			-X POST \
			-H "Accept: application/vnd.github+json" \
			-H "Authorization: Bearer $ACCESS_TOKEN" \
			-H "X-Github-Api-Version: 2022-11-28" \
			-H "Content-Type: application/octet-stream" \
			https://uploads.github.com/repos/$OWNER/$REPOSITORY/releases/$RELEASEID/assets?name=$lpkgfile \
			--data-binary "@$lpkgfile"
		
		# Upload BookThief .DEB package
		curl -L \
			-X POST \
			-H "Accept: application/vnd.github+json" \
			-H "Authorization: Bearer $ACCESS_TOKEN" \
			-H "X-Github-Api-Version: 2022-11-28" \
			-H "Content-Type: application/octet-stream" \
			https://uploads.github.com/repos/$OWNER/$REPOSITORY/releases/$RELEASEID/assets?name=$btpkgfile \
			--data-binary "@$btpkgfile"
		
		# Upload BookThief Win64 Installer package
		curl -L \
			-X POST \
			-H "Accept: application/vnd.github+json" \
			-H "Authorization: Bearer $ACCESS_TOKEN" \
			-H "X-Github-Api-Version: 2022-11-28" \
			-H "Content-Type: application/octet-stream" \
			https://uploads.github.com/repos/$OWNER/$REPOSITORY/releases/$RELEASEID/assets?name=$btwininstallerfile \
			--data-binary "@$btwininstallerfile"
		
		cd $initdir/$nowvar
	fi
	
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
