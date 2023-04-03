#!/bin/bash

# Configure Inno Setup location here (ISCC variable)
# For building a BookThief Windows Installer package
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
#  - release
#    All release binaries are moved to this directory after being built

initdir=$(pwd)
nowvar=$(date +%Y-%h-%d-%H%M%S)

# Variables for build-farm Virtual Machines
SSHPASSWORD="debianpassword"
SSHUSER="debian"
SSHPORT="22222"


basereleasedir=""


# Declare the lists of packages we'll be building
# These arrays will be populated with package names from ${pkgswecanbebuild}
buildbasepkgs=()
buildi386pkgs=()
buildarm64pkgs=()
ccwinpkgs=()

thisarchitecture="amd64"

# Array of packages this script can build
pkgswecanbuild=("polonius" "liesel" "bookthief" "ocrshot" "randomtext")


# Key -> Value map of package name -> Git URL
declare -A urls
urls=()
urls+=(["polonius"]="https://github.com/rail5/polonius.git")
urls+=(["liesel"]="https://github.com/rail5/liesel.git")
urls+=(["bookthief"]="https://github.com/rail5/bookthief.git")
urls+=(["ocrshot"]="https://github.com/rail5/ocrshot.git")
urls+=(["randomtext"]="https://github.com/rail5/randomtext.git")


# GitHub info: Username and Access Token
OWNER="rail5"
ACCESS_TOKEN=$(gpg -d /etc/git/github-token.gpg 2>/dev/null)


# Location of the Debian Repo we may push to
# This script can push to a Debian Repository hosted on GitHub pages (or similar)
gitdebianrepo="https://github.com/rail5/ppa.git"
repodirectory="$initdir/$nowvar/repo/debian"


function setup_build_environment() {
	mkdir -p "$initdir/$nowvar"

	mkdir "$initdir/$nowvar/srconly"
	mkdir "$initdir/$nowvar/deb"
	mkdir "$initdir/$nowvar/release"

	basereleasedir="$initdir/$nowvar/release/"
}
	

function build_package_universal() {
	if [[ $# -lt 6 ]]; then
		echo "bag args to build_package_universal" && exit 1
	fi
	
	local PKGNAME="$1" GITURL="$2" BASEBUILD="$3" I386BUILD="$4" ARM64BUILD="$5" WINBUILD="$6" CLONESOURCES=0
	
	# Variables:
	## PKGNAME, GITURL: Self-explanatory
	## BASEBUILD:
	### 1 or 0: are we cloning the sources & building the .deb on this local machine?
	## I386BUILD:
	### 1 or 0: are we connecting to the i386 build-farm VM and building there?
	## ARM64BUILD:
	### 1 or 0: are we connecting to the arm64 build-farm VM and building there?
	## WINBUILD:
	### 1 or 0: are we cloning the sources and running 'make windows' in the source tree?
	
	# Winbuild should only be run if basebuild has already been run & sources already cloned
	
	# Start by creating directories:
	##	srcdir: Source-only directory, also Git root
	##	builddir: Directory where we build the .deb package
	##	releasedir: Directory where we move the .debs after they've been built
	local srcdir="$initdir/$nowvar/srconly/$PKGNAME"
	local builddir="$initdir/$nowvar/deb/$PKGNAME"
	local releasedir="$basereleasedir/$PKGNAME"
	
	mkdir -p "$builddir"
	mkdir -p "$releasedir"
	
	if [[ BASEBUILD -eq 1 ]]; then
		# Pull the source code from GITURL into directory named PKGNAME
		cd "$initdir/$nowvar/srconly"
		git clone "$GITURL" "$PKGNAME"
		
		cp -rv "$srcdir/"* "$builddir/"
		
		# Move to the build directory, don't mess with the source directory
		echo "MOVING TO $builddir"
		cd "$builddir"
		
		# Build package
		debuild
	fi
	
	if [[ I386BUILD -eq 1 ]]; then
		# Build on the build-farm i386 VM
		# This should only run if the VM has been turned on already
		build_other_arch "$PKGNAME" "$GITURL" "i386"
		mv "$initdir/build-farm/packages/debs/"*.deb "$releasedir/"
	fi
	
	if [[ ARM64BUILD -eq 1 ]]; then
		# Build on the build-farm arm64 VM
		# This should only run if the VM has been turned on already
		build_other_arch "$PKGNAME" "$GITURL" "arm64"
		mv "$initdir/build-farm/packages/debs/"*.deb "$releasedir/"
	fi
	
	if [[ WINBUILD -eq 1 ]]; then
		# Cross-compile to Windows using 'make' target 'windows'
		echo "MOVING TO $builddir"
		cd "$builddir"
		make windows
		mv "$builddir/"*.exe "$releasedir/"
	fi
	
	# Move packages to 'release' directory
	mv "$initdir/$nowvar/deb/"*.deb "$releasedir/"
	mv "$initdir/$nowvar/deb/"*.tar.gz "$releasedir/"
	mv "$initdir/$nowvar/deb/"*.dsc "$releasedir/"
	mv "$initdir/$nowvar/deb/"*.build "$releasedir/"
	mv "$initdir/$nowvar/deb/"*.buildinfo "$releasedir/"
	mv "$initdir/$nowvar/deb/"*.changes "$releasedir/"
}

function clean_up() {
	# Keep only the 'release' and 'srconly' directories
	rm -rf "$initdir/$nowvar/deb"
}

function start_build_vm() {
	# Turn on the VM
	if [[ $# != 1 ]]; then
		echo "bad args to start_build_vm" && exit 2
	fi
	
	local ARCH="$1"
	
	# Set ARCHDIR
	## ARCHDIR is the subdirectory underneath 'build-farm/' containing the QEMU VM for the arch in question
	## IE, if we want to build using the VM stored in ./build-farm/debian-stable-arm64
	## Then we would set ARCHDIR="debian-stable-arm64"
	local ARCHDIR=""
	
	if [[ "$ARCH" == "i386" ]]; then
		ARCHDIR="debian-stable-i386"
		
		cd "$initdir/build-farm/$ARCHDIR"
		
		make boot-nodisplay &
		# Give the VM some time to come online
		sleep 15
		
	elif [[ "$ARCH" == "arm64" ]]; then
		ARCHDIR="debian-stable-arm64"
	
		cd "$initdir/build-farm/$ARCHDIR"
		
		make boot-nodisplay &
		# Give the VM some time to come online (ARM emulation takes a lot longer)
		sleep 60
	fi
	
	sshpass -p $SSHPASSWORD ssh -o "UserKnownHostsFile=/dev/null" -o "StrictHostKeyChecking=no" -tt -p $SSHPORT $SSHUSER@127.0.0.1 << EOF

sudo apt update -y
sudo apt upgrade -y

exit
EOF
}

function shutdown_build_vm() {
	# Connect to it on SSH and send the shutdown command
	sshpass -p $SSHPASSWORD ssh -o "UserKnownHostsFile=/dev/null" -o "StrictHostKeyChecking=no" -tt -p $SSHPORT $SSHUSER@127.0.0.1 << EOF
sudo shutdown now

EOF
}

function build_other_arch() {
	if [[ $# != 3 ]]; then
		echo "bag args to build_other_arch" && exit 3
	fi
	
	local PKGNAME="$1" GITURL="$2" ARCH="$3"
	
	# The following commands (After sshpass / ssh, until 'EOF') are passed directly to the VM
	# Here we connect and build the packages
	sshpass -p $SSHPASSWORD ssh -o "UserKnownHostsFile=/dev/null" -o "StrictHostKeyChecking=no" -tt -p $SSHPORT $SSHUSER@127.0.0.1 << EOF
mkdir -p /home/debian/build/src
mkdir -p /home/debian/build/pkg

echo "Cleaning build environment"
cd /home/debian/build/pkg
rm -rf ./*
cd /home/debian/build/src
rm -rf ./*

echo "Getting source"
git clone "$GITURL" "$PKGNAME"

cd "$PKGNAME"
debuild -us -uc

cd ..
rm -rf "$PKGNAME/"

tar -czvf /home/debian/build/pkg/packages.tar.gz ./

exit
EOF
	
	# Now we SCP download the packages to the host machine
	sshpass -p $SSHPASSWORD scp -o "UserKnownHostsFile=/dev/null" -o "StrictHostKeyChecking=no" -P $SSHPORT -r $SSHUSER@127.0.0.1:/home/debian/build/pkg/packages.tar.gz "$initdir/build-farm/packages/$ARCH/packages.tar.gz"
	
	cd "$initdir/build-farm/packages/$ARCH"
	tar -xvzf packages.tar.gz
	rm -f packages.tar.gz
	mv "$initdir/build-farm/packages/$ARCH/"*.deb "$initdir/build-farm/packages/debs/"
	rm -f "$initdir/build-farm/packages/$ARCH/"*
}

function build_all_pkgs_in_pkgarrays() {

	local buildingsomei386=0 buildingsomearm64=0
	if [[ ${#buildi386pkgs[@]} -gt 0 ]]; then
		buildingsomei386=1
	fi
	
	if [[ ${#buildarm64pkgs[@]} -gt 0 ]]; then
		buildingsomearm64=1
	fi

	for pkgname in "${buildbasepkgs[@]}"; do
		build_package_universal "$pkgname" "${urls[$pkgname]}" 1 0 0 0
	done
	
	for pkgname in "${ccwinpkgs[@]}"; do
		build_package_universal "$pkgname" "${urls[$pkgname]}" 0 0 0 1
	done
	
	if [[ buildingsomei386 -eq 1 ]]; then
		# Start the VM, build all the packages, and then shut it down
		start_build_vm "i386"
		for pkgname in "${buildi386pkgs[@]}"; do
			build_package_universal "$pkgname" "${urls[$pkgname]}" 0 1 0 0
		done
		shutdown_build_vm
		
		# Allow a few seconds for the VM to shut down before we potentially try to start another one
		sleep 10
	fi
	
	if [[ buildingsomearm64 -eq 1 ]]; then
		# Start the VM, build all the packages, and then shut it down
		start_build_vm "arm64"
		for pkgname in "${buildarm64pkgs[@]}"; do
			build_package_universal "$pkgname" "${urls[$pkgname]}" 0 0 1 0
		done
		shutdown_build_vm
	fi
}

function push_to_ubuntu_ppa() {
	echo "Not implemented yet"
}

function push_github_release_page() {
	if [[ $# != 1 ]]; then
		echo "bag args to push_github_release_page" && exit 4
	fi
	
	local PKGNAME="$1" CHANGELOG="" VERSION="" REPOSITORY=""
	
	# Get repo name from PKG url
	
	## This first line trims a URL like "https://github.com/user/repo.git" to just "repo.git"
	REPOSITORY=$(echo "${urls[$PKGNAME]}" | grep -P -o -e "/[^/]*\.git" | cut -c2-)
	
	## This second line removes the ".git" if it's there, making it just "repo"
	REPOSITORY="${REPOSITORY/.git/""}"
	
	# Get package info: Latest changelog entry + latest version number
	
	## Get changelog from package source using get-pkg-info.php -c
	CHANGELOG=$(php "$initdir/get-pkg-info.php" -i "$initdir/$nowvar/srconly/$PKGNAME" -c)
	
	## Get version number from package source using get-pkg-info.php -v
	VERSION=$(php "$initdir/get-pkg-info.php" -i "$initdir/$nowvar/srconly/$PKGNAME" -v)
	
	
	# Create GitHub Release
	
	## Make sure we're in the package's release directory
	cd $basereleasedir/$PKGNAME
	
	## Send POST request to GitHub API to create a release page
	curl -L \
			-X POST \
			-H "Accept: application/vnd.github+json" \
			-H "Authorization: Bearer $ACCESS_TOKEN" \
			-H "X-GitHub-Api-Version: 2022-11-28" \
			https://api.github.com/repos/$OWNER/$REPOSITORY/releases \
			-d "{\"tag_name\": \"v$VERSION\",
				\"target_commitish\": \"main\",
				\"name\": \"v$VERSION\",
				\"body\": \"$CHANGELOG\",
				\"draft\": false,
				\"prerelease\": false,
				\"generate_release_notes\": false}" > release-info
	
	## Get GitHub Release ID
	RELEASEID=$(php "$initdir/get-release-id.php" -i "$basereleasedir/$PKGNAME/release-info")
	
	## Declare array which will be filled with filenames of attachments (.debs, .exes)
	local list_of_pkg_files=()
	
	## Get the list of package files
	for file in $(ls); do
		list_of_pkg_files+=("$(echo $file | grep -P -e $PKGNAME.*.deb)")
		if [[ "$PKGNAME" != "bookthief" ]]; then
			# Don't add the plain old .exe file in the case of bookthief, we'll add the Installer instead
			list_of_pkg_files+=("$(echo $file | grep -P -e $PKGNAME.*.exe)")
		fi
	done
	
	if [[ "$PKGNAME" == "bookthief" ]]; then
		# Add the Windows Installer package if this is bookthief
		list_of_pkg_files+=("$(ls "$basereleasedir/bookthief/" | grep -P -o -e BookThief.*Installer.exe)")
		# Also add the Liesel .deb packages
		for extrafile in $(ls "$basereleasedir/liesel/"); do
			if [[ "$extrafile" != "" ]]; then
				list_of_pkg_files+=("$(echo $extrafile | grep -P -e liesel.*.deb)")
				cp "$basereleasedir/liesel/$extrafile" "$basereleasedir/bookthief/"
			fi
		done
	fi
	
	## Upload the attachments via POST request to the GitHub API
	for file in "${list_of_pkg_files[@]}"; do
		if [[ "$file" != "" ]]; then
			curl -L \
				-X POST \
				-H "Accept: application/vnd.github+json" \
				-H "Authorization: Bearer $ACCESS_TOKEN" \
				-H "X-Github-Api-Version: 2022-11-28" \
				-H "Content-Type: application/octet-stream" \
				https://uploads.github.com/repos/$OWNER/$REPOSITORY/releases/$RELEASEID/assets?name=$file \
				--data-binary "@$file"
		fi
	done
	
	cd $initdir
}

function prepare_ghpages_debian_repo() {
	cd "$initdir/$nowvar"
	
	# Clone the git repo into a directory called "repo"
	git clone "$gitdebianrepo" "repo"
}

function close_ghpages_debian_repo() {
	cd "$initdir/$nowvar/repo"
	
	# Push the changes we've made before calling this function
	git push origin
	
	# Move back to start
	cd $initdir
	
	# Clean up
	rm -rf "$initdir/$nowvar/repo"
}

function push_to_ghpages_debian_repo() {
	# Pushes a package to a Debian Repository hosted on GitHub Pages
	# The repo must be managed via 'reprepro'
	# This should be called AFTER prepare_ghpages_debian_repo() and BEFORE close_ghpages_debian_repo()
	
	if [[ $# != 1 ]]; then
		echo "bag args to push_to_ghpages_debian_repo" && exit 5
	fi
	
	local PKGNAME="$1" CHANGESFILE="" list_of_pkg_files=()
	
	cd "$initdir/$nowvar/repo"
	cd debian
	
	
	# Get the .changes file
	CHANGESFILE="$(ls "$basereleasedir/$PKGNAME/" | grep -P -e .changes | head -n 1)"
	
	# Add that to the repo
	reprepro -P optional include $debiandist "$basereleasedir/$PKGNAME/$CHANGESFILE"
	
	
	# Get the list of all .deb package files that are NOT marked with $thisarchitecture (default: amd64)
	for file in $(ls "$basereleasedir/$PKGNAME/"); do
		list_of_pkg_files+=("$(echo $file | grep -P -e $PKGNAME.*.deb | grep -v "$thisarchitecture")")
	done
	
	# Add those to the repo with includedeb
	for file in "${list_of_pkg_files[@]}"; do
		if [[ "$file" != "" ]]; then
			reprepro includedeb $debiandist "$basereleasedir/$PKGNAME/$file"
		fi
	done
	
	# Update indexes and commit changes
	cd "$initdir/$nowvar/repo"
	./update-indexes.sh
	git add --all
	git commit -m "Updated $PKGNAME"
}


function ask_user_build_pkg() {
	if [[ $# != 1 ]]; then
		echo "bag args to ask_user_build_pkg" && exit 6
	fi
	
	local PKGNAME="$1"
	
	while true; do
		read -p "Do you want to build $PKGNAME? (y/n) " yn
		case $yn in
			[Yy]* ) buildbasepkgs+=("$PKGNAME"); break;;
			[Nn]* ) return 1; break;;
			* ) echo "Answer yes or no";;
		esac
	done
	
	while true; do
		read -p "  $PKGNAME: Build on i386 as well? (y/n) " yn
		case $yn in
			[Yy]* ) buildi386pkgs+=("$PKGNAME"); break;;
			[Nn]* ) break;;
			* ) echo "Answer yes or no";;
		esac
	done
	
	while true; do
		read -p "  $PKGNAME: Build on arm64 as well? (y/n) " yn
		case $yn in
			[Yy]* ) buildarm64pkgs+=("$PKGNAME"); break;;
			[Nn]* ) break;;
			* ) echo "Answer yes or no";;
		esac
	done
	
	while true; do
		read -p "  $PKGNAME: Cross-compile to Windows as well? (y/n) " yn
		case $yn in
			[Yy]* ) ccwinpkgs+=("$PKGNAME"); break;;
			[Nn]* ) break;;
			* ) echo "Answer yes or no";;
		esac
	done
}

function ask_user_publish_to_deb_repo() {

	local pkgstopublish=()

	for pkgname in "${buildbasepkgs[@]}"; do
		while true; do
			read -p "Do you want to publish $pkgname to deb.rail5.org? (y/n) " yn
			case $yn in
				[Yy]* ) pkgstopublish+=("$pkgname"); break;;
				[Nn]* ) break;;
				* ) echo "Answer yes or no";;
			esac
		done
	done
	
	if [[ ${#pkgstopublish[@]} -gt 0 ]]; then
		echo "Publishing to repo..."
		
		prepare_ghpages_debian_repo
		
		for pkg in "${pkgstopublish[@]}"; do
			push_to_ghpages_debian_repo "$pkg"
		done
		
		close_ghpages_debian_repo
	fi
}

function ask_user_make_github_release_page() {
	
	local releasepagestomake=()
	
	for pkgname in "${buildbasepkgs[@]}"; do
		while true; do
			read -p "Do you want to make a GITHUB RELEASE PAGE for $pkgname? (y/n) " yn
			case $yn in
				[Yy]* ) releasepagestomake+=("$pkgname"); break;;
				[Nn]* ) break;;
				* ) echo "Answer yes or no";;
			esac
		done
	done
	
	if [[ ${#releasepagestomake[@]} -gt 0 ]]; then
		echo "Making release pages..."
		
		for pkg in "${releasepagestomake[@]}"; do
			push_github_release_page "$pkg"
		done
	fi
}

function make_bookthief_windows_installer() {
	if [[ $# != 1 ]]; then
		echo "bag args to make_bookthief_windows_installer" && exit 7
	fi
	
	local versionnumber="$1"
	
	cd "$initdir/$nowvar"
	
	wininstallerdirectory="$initdir/$nowvar/windowsinstaller"
	
	mkdir -p "$wininstallerdirectory"
	cd "$wininstallerdirectory"
	
	mkdir -p source/bookthief
	mkdir -p source/liesel
	mkdir pkg
	
	cp "$basereleasedir/bookthief/bookthief.exe" pkg/
	cp "$basereleasedir/liesel/liesel.exe" pkg/
	
	cp -r "$initdir/$nowvar/srconly/bookthief/"* source/bookthief/
	cp -r "$initdir/$nowvar/srconly/liesel/"* source/liesel/
	cp "$initdir/$nowvar/srconly/bookthief/LICENSE" pkg/LICENSE.txt
	
	php "$initdir/autobuild.php" -b -v "$versionnumber" -p "$wininstallerdirectory" > "$wininstallerdirectory/bt-$versionnumber.iss"
	
	wine "$ISCC" ./bt-$versionnumber.iss
	
	mv "./pkg/BookThief-$versionnumber-Installer.exe" "$basereleasedir/bookthief/"
}

function maybe_build_bookthief_windows_installer() {
	if ([[ "${ccwinpkgs[*]}" =~ "bookthief" ]] && [[ "${ccwinpkgs[*]}" =~ "liesel" ]]); then
		local btversion=$(php "$initdir/get-pkg-info.php" -i "$initdir/$nowvar/srconly/bookthief" -v)
		
		make_bookthief_windows_installer "$btversion"
	fi
}

# And now the program:

setup_build_environment

for pkgname in "${pkgswecanbuild[@]}"; do
	ask_user_build_pkg "$pkgname"
done

build_all_pkgs_in_pkgarrays
clean_up

maybe_build_bookthief_windows_installer

ask_user_publish_to_deb_repo
ask_user_make_github_release_page
