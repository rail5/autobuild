#!/bin/bash

# Directory structure:
#  - deb
#    Packages are built in this directory before being move to 'release'
#
#  - srconly
#    Contains the source of the packages
#
#  - release
#    All release binaries are moved to this directory after being built

scriptfile=$(readlink -f "$0")
initdir=$(dirname "$scriptfile")
pkgs_build_base_directory=builds/$(date +%Y-%h-%d-%H%M%S)


basereleasedir=""


# Declare the lists of packages we'll be building
# These arrays will be populated with the packages selected by the user
buildbasepkgs=()
buildi386pkgs=()
buildarm64pkgs=()


# How will we distribute the built packages?
# These arrays will be populated etc etc
pkgstopublish=()
releasepagestomake=()


# Load configuration
. ./includes/config.sh


function setup_build_environment() {
	mkdir -p "$initdir/$pkgs_build_base_directory"

	mkdir "$initdir/$pkgs_build_base_directory/srconly"
	mkdir "$initdir/$pkgs_build_base_directory/deb"
	mkdir "$initdir/$pkgs_build_base_directory/release"

	basereleasedir="$initdir/$pkgs_build_base_directory/release/"
}
	

function build_package_universal() {
	if [[ $# -lt 5 ]]; then
		echo "bag args to build_package_universal" && exit 1
	fi
	
	local PKGNAME="$1" GITURL="$2" BASEBUILD="$3" I386BUILD="$4" ARM64BUILD="$5" CLONESOURCES=0
	
	# Variables:
	## PKGNAME, GITURL: Self-explanatory
	## BASEBUILD:
	### 1 or 0: are we cloning the sources & building the .deb on this local machine?
	## I386BUILD:
	### 1 or 0: are we connecting to the i386 build-farm VM and building there?
	## ARM64BUILD:
	### 1 or 0: are we connecting to the arm64 build-farm VM and building there?
	
	# Start by creating directories:
	##	srcdir: Source-only directory, also Git root
	##	builddir: Directory where we build the .deb package
	##	releasedir: Directory where we move the .debs after they've been built
	local srcdir="$initdir/$pkgs_build_base_directory/srconly/$PKGNAME"
	local builddir="$initdir/$pkgs_build_base_directory/deb/$PKGNAME"
	local releasedir="$basereleasedir/$PKGNAME"
	
	mkdir -p "$builddir"
	mkdir -p "$releasedir"
	
	if [[ BASEBUILD -eq 1 ]]; then
		# Pull the source code from GITURL into directory named PKGNAME
		cd "$initdir/$pkgs_build_base_directory/srconly"
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
	
	# Move packages to 'release' directory
	mv "$initdir/$pkgs_build_base_directory/deb/"*.deb "$releasedir/"
	mv "$initdir/$pkgs_build_base_directory/deb/"*.tar.gz "$releasedir/"
	mv "$initdir/$pkgs_build_base_directory/deb/"*.dsc "$releasedir/"
	mv "$initdir/$pkgs_build_base_directory/deb/"*.build "$releasedir/"
	mv "$initdir/$pkgs_build_base_directory/deb/"*.buildinfo "$releasedir/"
	mv "$initdir/$pkgs_build_base_directory/deb/"*.changes "$releasedir/"
}

function clean_up() {
	# Keep only the 'release' and 'srconly' directories
	rm -rf "$initdir/$pkgs_build_base_directory/deb"
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
		
		make boot-notelnet &
		# Give the VM some time to come online
		sleep 15
		
	elif [[ "$ARCH" == "arm64" ]]; then
		ARCHDIR="debian-stable-arm64"
	
		cd "$initdir/build-farm/$ARCHDIR"
		
		make boot-notelnet &
		# Give the VM some time to come online (ARM emulation takes a lot longer)
		sleep 60
	fi
	
	sshpass -p $SSHPASSWORD ssh -o "UserKnownHostsFile=/dev/null" -o "StrictHostKeyChecking=no" -tt -p $SSHPORT $SSHUSER@127.0.0.1 >/dev/null 2>&1 << EOF

(sudo apt update -y >/dev/null 2>&1 && sudo apt upgrade -y >/dev/null 2>&1) & disown

exit
EOF
}

function shutdown_build_vm() {
	# Connect to it on SSH and send the shutdown command
	sshpass -p $SSHPASSWORD ssh -o "UserKnownHostsFile=/dev/null" -o "StrictHostKeyChecking=no" -tt -p $SSHPORT $SSHUSER@127.0.0.1 >/dev/null 2>&1 << EOF
sudo shutdown now >/dev/null 2>&1

EOF
}

function build_other_arch() {
	if [[ $# != 3 ]]; then
		echo "bag args to build_other_arch" && exit 3
	fi
	
	local PKGNAME="$1" GITURL="$2" ARCH="$3"
	
	# The following commands (After sshpass / ssh, until 'EOF') are passed directly to the VM
	# Here we connect and build the packages
	sshpass -p $SSHPASSWORD ssh -o "UserKnownHostsFile=/dev/null" -o "StrictHostKeyChecking=no" -tt -p $SSHPORT $SSHUSER@127.0.0.1 >/dev/null 2>&1 << EOF
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
		build_package_universal "$pkgname" "${packages[$pkgname]}" 1 0 0
	done
	
	if [[ buildingsomei386 -eq 1 ]]; then
		# Start the VM, build all the packages, and then shut it down
		start_build_vm "i386"
		for pkgname in "${buildi386pkgs[@]}"; do
			build_package_universal "$pkgname" "${packages[$pkgname]}" 0 1 0
		done
		shutdown_build_vm
		
		# Allow a few seconds for the VM to shut down before we potentially try to start another one
		sleep 10
	fi
	
	if [[ buildingsomearm64 -eq 1 ]]; then
		# Start the VM, build all the packages, and then shut it down
		start_build_vm "arm64"
		for pkgname in "${buildarm64pkgs[@]}"; do
			build_package_universal "$pkgname" "${packages[$pkgname]}" 0 0 1
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
	REPOSITORY=$(echo "${packages[$PKGNAME]}" | grep -P -o -e "/[^/]*\.git" | cut -c2-)
	
	## This second line removes the ".git" if it's there, making it just "repo"
	REPOSITORY="${REPOSITORY/.git/""}"
	
	# Get package info: Latest changelog entry + latest version number
	
	## Get changelog from package source using dpkg-parsechangelog (dpkg-dev package)
	### Pipe into sed to:
	### (1) Remove newline chars
	### (2) Strip down to JUST the latest changelog message (Not any other metadata, just the message the author wrote) (Everything after the first '* ')
	CHANGELOG=$(dpkg-parsechangelog -l "$initdir/$pkgs_build_base_directory/srconly/$PKGNAME/debian/changelog" --show-field changes | sed -z 's/\n//g' | sed -n 's/.*\* //p')

	### Finally, check if "double-spaces" exist (The double-spaces come after we remove the original newline chars, if the original changelog message spanned multiple lines). If so:
	### (3) Replace double-spaces ('  ') with '\n' (literal)
	if (echo "$CHANGELOG" | grep "  " >/dev/null); then
		CHANGELOG=$(echo -n "$CHANGELOG" | sed -n 's/  /\\n/p')
	fi

	## Get version number from package source using dpkg-parsechangelog
	### Pipe into sed to remove the ending newline char
	VERSION=$(dpkg-parsechangelog -l "$initdir/$pkgs_build_base_directory/srconly/$PKGNAME/debian/changelog" --show-field version | sed -z 's/\n//g')
	
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
	RELEASEID=$(cat "$basereleasedir/$PKGNAME/release-info" | jq -r '.id')
	
	## Declare array which will be filled with filenames of attachments (.debs, .exes)
	local list_of_pkg_files=()
	
	## Get the list of package files
	for file in $(ls); do
		list_of_pkg_files+=("$(echo $file | grep -P -e $PKGNAME.*.deb)")
	done
	
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
	# Clone the git repo into a local directory
	cd "$initdir"
	git clone "$git_debianrepo" "$local_repodirectory"
	cd "$local_repodirectory"
	git pull
}

function close_ghpages_debian_repo() {
	cd "$initdir/$local_repodirectory"
	
	# Push the changes we've made before calling this function
	git push origin
	
	# Move back to start
	cd "$initdir"
}

function push_to_ghpages_debian_repo() {
	# Pushes a package to a Debian Repository hosted on GitHub Pages
	# The repo must be managed via 'reprepro'
	# This should be called AFTER prepare_ghpages_debian_repo() and BEFORE close_ghpages_debian_repo()
	
	if [[ $# != 1 ]]; then
		echo "bag args to push_to_ghpages_debian_repo" && exit 5
	fi
	
	local PKGNAME="$1" CHANGESFILE="" list_of_pkg_files=()
	
	cd "$initdir/$local_repodirectory"
	cd debian
	
	
	# Get the .changes file
	CHANGESFILE="$(ls "$basereleasedir/$PKGNAME/" | grep -P -e .changes | head -n 1)"
	
	# Add that to the repo
	reprepro -P optional include $debian_distribution "$basereleasedir/$PKGNAME/$CHANGESFILE"
	
	
	# Get the list of all .deb package files that are NOT marked with $host_architecture (default: amd64)
	for file in $(ls "$basereleasedir/$PKGNAME/"); do
		list_of_pkg_files+=("$(echo $file | grep -P -e $PKGNAME.*.deb | grep -v "$host_architecture")")
	done
	
	# Add those to the repo with includedeb
	for file in "${list_of_pkg_files[@]}"; do
		if [[ "$file" != "" ]]; then
			reprepro includedeb $debian_distribution "$basereleasedir/$PKGNAME/$file"
		fi
	done
	
	# Update indexes and commit changes
	cd "$initdir/$local_repodirectory"
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
}

function ask_user_publish_to_deb_repo() {
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
}

function maybe_publish_to_deb_repo() {
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
}

function maybe_make_github_release_page() {
	if [[ ${#releasepagestomake[@]} -gt 0 ]]; then
		echo "Making release pages..."
		
		for pkg in "${releasepagestomake[@]}"; do
			push_github_release_page "$pkg"
		done
	fi
}

function display_help() {
	echo "autobuild"
	echo "Copyright (C) 2023 rail5"
	echo ""
	echo "This program comes with ABSOLUTELY NO WARRANTY."
	echo "This is free software (GNU GPL V3), and you are welcome to redistribute it under certain conditions."
	echo ""
	echo "You should edit CONFIG and run setup before using this script"
	echo ""
	echo "Options:"
	echo ""
	echo "  -p"
	echo "  --package"
	echo "    Add a package to the build list"
	echo ""
	echo "  -1"
	echo "  --i386"
	echo "    Build packages on the i386 Build Farm VM"
	echo ""
	echo "  -2"
	echo "  --arm64"
	echo "    Build packages on the arm64 Build Farm VM"
	echo ""
	echo "  -d"
	echo "  --debian-repo"
	echo "    Distribute built packages to a Git-based Debian Repository managed with reprepro"
	echo ""
	echo "  -g"
	echo "  --github-page"
	echo "    Create a Release page on the built packages' Github repositories"
	echo ""
	echo "Example:"
	echo "  autobuild -12dg -p liesel -p bookthief -p polonius"
	echo "  autobuild --i386 --arm64 --debian-repo --github-page --package liesel --package bookthief --package polonius"
	echo ""
	echo "If no arguments are provided, the script will run in 'interactive mode'"
}


# And now the program:


# Check if the user has provided us arguments for non-interactive mode

TEMP=$(getopt -o 12dghp: --long i386,arm64,debian-repo,github-page,help,package: \
              -n 'autobuild' -- "$@")

if [ $? != 0 ] ; then echo "Terminating..." >&2 ; exit 1 ; fi

eval set -- "$TEMP"

interactive_mode=true # By default, until we received --package "something"

cross_to_i386=false
cross_to_arm64=false
distribute_to_debian_repo=false
distribute_to_github_page=false

while true; do
	case "$1" in
		-1 | --i386 )
			export cross_to_i386=true; shift ;;
		-2 | --arm64 )
			cross_to_arm64=true; shift ;;
		-d | --debian-repo )
			distribute_to_debian_repo=true; shift ;;
		-g | --github-page )
		distribute_to_github_page=true; shift ;;
		-h | --help )
			display_help; exit 0; shift ;;
		-p | --package )
			if [ ${packages[$2]+1} ]; then
				buildbasepkgs+=("$2");
				interactive_mode=false;
				echo "Building $2"
			else
				echo "ERROR: Package '$2' not in CONFIG!";
			fi;
			shift 2 ;;
		-- ) shift; break ;;
		* ) break ;;
	esac
done

setup_build_environment

if [ $interactive_mode == true ]; then
	for pkgname in "${!packages[@]}"; do
		ask_user_build_pkg "$pkgname"
	done
else
	# Non-interactive mode

	# Check if we're using the build farm
	if [ $cross_to_i386 == true ]; then
		echo "Crossing to i386"
		for pkgname in "${buildbasepkgs[@]}"; do
			buildi386pkgs+=("$pkgname")
		done
	fi

	if [ $cross_to_arm64 == true ]; then
		echo "Crossing to arm64"
		for pkgname in "${buildbasepkgs[@]}"; do
			buildarm64pkgs+=("$pkgname")
		done
	fi

	# Check if and how we're distributing the built packages
	if [ $distribute_to_debian_repo == true ]; then
		for pkgname in "${buildbasepkgs[@]}"; do
			pkgstopublish+=("$pkgname")
		done
	fi

	if [ $distribute_to_github_page == true ]; then
		for pkgname in "${buildbasepkgs[@]}"; do
			releasepagestomake+=("$pkgname")
		done
	fi
fi

build_all_pkgs_in_pkgarrays
clean_up

if [ interactive_mode == true ]; then
	ask_user_publish_to_deb_repo
	ask_user_make_github_release_page
fi

maybe_publish_to_deb_repo
maybe_make_github_release_page