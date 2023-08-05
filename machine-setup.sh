#!/bin/bash

## Environment variables ##
packagemanager="apt"
installcommand="install"
updatecommand="update"

## Needed if we're installing Inno Setup later
add32bitcommand="dpkg --add-architecture i386"
bit32winepkg="wine32"

# Build Dependencies
basepkgslist="build-essential gcc g++ make git wget tar curl"

bookthiefdeps="fpc-3.2.2 lazarus lcl-2.2 lcl-utils-2.2 fp-units-misc-3.2.2"

lieseldeps="graphicsmagick-libmagick-dev-compat libmagick++-6-headers libfontconfig1-dev libpoppler-cpp-dev libhpdf-dev"

packagingdeps="devscripts make sed unzip xz-utils"

extrapackagingdeps="wine wine64 php-cli reprepro"

buildfarmdeps="sshpass libarchive-tools syslinux syslinux-utils cpio genisoimage coreutils qemu-system qemu-system-x86 qemu-utils util-linux"

mxedeps="autoconf automake autopoint bash bison bzip2 flex g++ g++-multilib gettext git gperf intltool libc6-dev-i386 libgdk-pixbuf2.0-dev libltdl-dev libssl-dev libtool-bin libxml-parser-perl lzip make openssl p7zip-full patch perl python-is-python3 ruby sed unzip wget xz-utils python3-mako"

# For restoring the CWD
initdir=`pwd`

###########################
## If you wanted to
## build on Arch, for example,
## you would change packagemanager to "pacman"
## and installcommand to (I think) "-Syu"
## and figure out the corresponding package names etc
## I don't use arch, so I don't know, but you get the idea



echo "This script is to set up your machine to compile BookThief+Liesel"
echo "I often flash my system and start fresh just out of old habit, so I use this when I do"
echo ""
echo ""
echo "This script assumes you're running Debian, and uses the APT package manager"
echo "If you want to run this on some other distribution, you can edit this script and replace 'apt' with your system's package manager, but I can't guarantee the packages will be named the same, or even be present"
echo "If you want to make those edits, there are environment variables at the top of the script"
echo ""
echo ""

echo "----"
echo "----"

while true; do
	read -p "Ready? (y/n) " yn
	case $yn in
		[Yy]* ) echo "Alright then"; break;;
		[Nn]* ) exit 1; break;;
		* ) echo "Answer yes or no";;
	esac
done

echo "----"
echo "----"

echo "Updating package lists..."

sudo $packagemanager $updatecommand

echo "----"
echo "----"
echo "Base necessary packages are:"
echo "$basepkgslist $bookthiefdeps $lieseldeps $packagingdeps $extrapackagingdeps"

installingbasedeps=0
while true; do
	read -p "Do you want to install these now? (y/n) " yn
	case $yn in
		[Yy]* ) echo "Alright then"; installingbasedeps=1; break;;
		[Nn]* ) echo "Moving on"; break;;
		* ) echo "Answer yes or no";;
	esac
done

if [[ installingbasedeps -eq 1 ]]; then

	echo "Installing build dependencies..."

	sudo $packagemanager $installcommand $basepkgslist $bookthiefdeps $lieseldeps $packagingdeps $extrapackagingdeps

	echo "----"
	echo "----"
	echo "----"
	echo "----"

	echo "Build dependencies installed"
	echo "--"

fi

setupgithubhttps=0
while true; do
	read -p "Do you want to set up GitHub over HTTPS? (y/n) " yn
	case $yn in
		[Yy]* ) echo "Alright then"; setupgithubhttps=1; break;;
		[Nn]* ) echo "Moving on"; break;;
		* ) echo "Answer yes or no";;
	esac
done

if [[ setupgithubhttps -eq 1 ]]; then
	
	read -p "Enter the email address associated with your GPG key: " gpgemail
	read -p "Enter your GitHub username: " ghuser
	read -p "Enter your GitHub Access Token: " ghtoken
	
	echo "machine github.com" > github-credentials
	echo "" >> github-credentials
	echo "login $ghuser" >> github-credentials
	echo "" >> github-credentials
	echo "password $ghtoken" >> github-credentials
	echo "" >> github-credentials
	echo "protocol https" >> github-credentials
	
	echo "$ghtoken" > github-token
	
	gpg --recipient $gpgemail -e ./github-credentials
	gpg --recipient $gpgemail -e ./github-token
	
	rm ./github-credentials
	rm ./github-token
	
	echo "#!/bin/bash" > credential-helper
	echo "" >> credential-helper
	echo "/usr/share/doc/git/contrib/credential/netrc/git-credential-netrc.perl -f /etc/git/github-credentials.gpg get" >> credential-helper
	
	ghreadyok=0
	echo "GitHub credential files will be moved to /etc/git/"
	echo "And git config variables will be set"
	while true; do
		read -p "Sound good? (y/n) " yn
		case $yn in
			[Yy]* ) echo "Alright then"; ghreadyok=1; break;;
			[Nn]* ) echo "Cancelling"; break;;
			* ) echo "Answer yes or no";
		esac
	done
	
	if [[ ghreadyok -eq 1 ]]; then
		sudo mkdir -p /etc/git
		sudo mv ./github-credentials.gpg /etc/git/
		sudo mv ./github-token.gpg /etc/git/
		sudo mv ./credential-helper /etc/git/
		sudo chmod +x /etc/git/credential-helper
		sudo chmod +x /usr/share/doc/git/contrib/credential/netrc/git-credential-netrc.perl
		
		git config --global credential.helper /etc/git/credential-helper
		git config --global user.name $ghuser
		
		echo "All set up!"
	fi
fi


echo "--"
echo "This script can also set up a virtual-machine Build Farm"
echo "This can be used to build packages on other architectures or for other distributions"
echo "This requires QEMU and a few more free (libre) utilities as well"
echo "Currently the build farm contains the following VMs:"
echo "  - Debian Stable i386"
echo "  - Debian Stable arm64"
echo "Setting the i386 VM up generally takes about 20-30 minutes"
echo "However, ARM VMs can take much longer (~2 hours each)"
echo "--"

buildingvms=0

while true; do
	read -p "Do you want to automatically prepare the Build Farm? (y/n) " yn
	case $yn in
		[Yy]* ) echo "Alright then"; buildingvms=1; break;;
		[Nn]* ) echo "Moving on"; break;;
		* ) echo "Answer yes or no";;
	esac
done

if [[ buildingvms -eq 1 ]]; then
	sudo $packagemanager $installcommand $buildfarmdeps
	
	cd $initdir/build-farm
	
	echo "  Creating i386 VM..."
	
	cd debian-stable-i386
	
	./set-install-packages.sh $basepkgslist $bookthiefdeps $lieseldeps $packagingdeps
	
	make download
	make image
	make boot-install
	make clean
	
	echo "  Created i386 VM."
	echo "  Creating arm64 VM..."
	
	cd ../debian-stable-arm64
	
	./set-install-packages.sh $basepkgslist $bookthiefdeps $lieseldeps $packagingdeps
	
	make download
	make image
	make boot-install
	make clean
	
	echo "  Created arm64 VM."
fi

echo "--"
echo "This script can also set up cross-compilation"
echo "MXE is required to cross-compile Liesel (core) Windows binaries"
echo "The FPC cross-compiler is required to build BookThief (GUI) Windows binaries"
echo "And Inno Setup 6 (via Wine) is required to build the Windows installer package"
echo "Setting up for cross-compilation (particularly, building the MXE packages) can take a really, really long time"
echo "--"

while true; do
	read -p "Do you want to prepare the cross-compiler as well? (y/n) " yn
	case $yn in
		[Yy]* ) echo "Alright then"; break;;
		[Nn]* ) exit 1; break;;
		* ) echo "Answer yes or no";;
	esac
done

echo "Installing CC dependencies"
sudo $packagemanager $installcommand $mxedeps

installingmxe=0

echo "---"
echo "MXE (the M Cross Environment) is necessary in order to cross-compile Liesel"
echo "This script can automatically download and compile MXE, with GCC11, GraphicsMagick and Poppler"
echo ""
while true; do
	read -p "Do you want to download and build MXE? (y/n) " yn
	case $yn in
		[Yy]* ) installingmxe=1; break;;
		[Nn]* ) installingmxe=0; break;;
		* ) echo "Answer yes or no";;
	esac
done

if [[ installingmxe -eq 1 ]]; then

	echo "Installing MXE to /opt/mxe"

	cd /opt

	sudo git clone https://github.com/mxe/mxe.git

	cd mxe

	sudo echo "MXE_PLUGIN_DIRS := plugins/gcc11" > ./settings.mk
	sudo echo "MXE_TARGETS := x86_64-w64-mingw32.static" >> ./settings.mk

	echo "----"
	echo "Building the GCC cross-compiler"
	echo "----"

	sudo make gcc -j 8

	echo "----"
	echo "Adding /opt/mxe/bin and /opt/mxe/usr/bin/ to \$PATH via /etc/profile"
	echo "----"
	### NOTE: 'sudo' does not redirect with >
	### But, also, this never worked anyway. Why did you keep it?
	sudo echo "" >> /etc/profile
	sudo echo "export PATH=/opt/mxe/usr/bin:/opt/mxe/bin:\$PATH" >> /etc/profile

	export PATH=/opt/mxe/usr/bin:/opt/mxe/bin:$PATH

	echo "----"
	echo "Cross-compiling Liesel's dependencies"
	echo "----"

	sudo make graphicsmagick poppler libharu -j 8
fi

installingfpc=0

echo "---"
echo "FPC (the Free Pascal Compiler) 3.2.0 is necessary in order to compile BookThief"
echo "This script has already installed a native version of FPC 3.2.0, but can also download and compile the FPC cross-compiler in order to build BookThief for Windows"
echo ""
while true; do
	read -p "Do you want to download and build FPC 3.2.0? (y/n) " yn
	case $yn in
		[Yy]* ) installingfpc=1; break;;
		[Nn]* ) installingfpc=0; break;;
		* ) echo "Answer yes or no";;
	esac
done

if [[ installingfpc -eq 1 ]]; then

	echo "----"
	echo "----"
	echo "----"
	echo "----"
	echo "Downloading FPC source code (Free Pascal Compiler) for BookThief"
	echo "----"
	echo "----"
	echo "----"
	echo "----"

	cd /opt/
	sudo mkdir fpc
	cd fpc

	sudo wget https://deac-ams.dl.sourceforge.net/project/freepascal/Source/3.2.0/fpc-3.2.0.source.tar.gz

	echo "--"
	echo "Unpacking"
	echo "--"

	sudo tar -xvzf fpc-3.2.0.source.tar.gz

	cd fpc-3.2.0

	export FPCVER="3.2.0"

	echo "--"
	echo "Compiling FPC win64 cross-compiler for BookThief"
	echo "--"
	sudo make clean all OS_TARGET=win64 CPU_TARGET=x86_64

	sudo make crossinstall OS_TARGET=win64 CPU_TARGET=x86_64 INSTALL_PREFIX=/usr

	sudo ln -sf /usr/lib/fpc/3.2.0/ppcrossx64 /usr/bin/ppcrossx64

	echo "------------------"
	echo "------------------"
	grep Fu /etc/fpc.cfg | grep fpcversion
	echo "------------------"
	echo "------------------"
	echo "If you see the line:"
	echo "-Fu/usr/lib/fpc/\$fpcversion/units/\$fpctarget/*"
	echo "In the text just above (between the ----'s), then everything's fine. If you don't, this script can add that to /etc/fpc.cfg"
	echo ""

addingfpcline=0

	while true; do
		read -p "Do you need the script to add that line to /etc/fpc.cfg? (Check.) (y/n) " yn
		case $yn in
			[Yy]* ) addingfpcline=1; break;;
			[Nn]* ) addingfpcline=0; break;;
			* ) echo "Answer yes or no";;
		esac
	done
	
	if [[ addingfpcline -eq 1 ]]; then
		sudo echo "" >> /etc/fpc.cfg
		sudo echo "-Fu/usr/lib/fpc/\$fpcversion/units/\$fpctarget/*" >> /etc/fpc.cfg
		sudo echo "Line added to bottom of /etc/fpc.cfg"
	fi

fi

installinginno=0

echo "---"
echo "Inno Setup 6 is necessary in order to build the Windows Installer Package"
echo "This script can download the Inno Setup 6 installer, and open it via Wine"
echo "This requires installing Wine's 32 bit libraries"
echo "You also need a functioning X-Server, since Inno Setup runs as a Graphical installer"
echo ""
while true; do
	read -p "Do you want to download and install Inno Setup 6? (y/n) " yn
	case $yn in
		[Yy]* ) installinginno=1; break;;
		[Nn]* ) installinginno=0; break;;
		* ) echo "Answer yes or no";;
	esac
done

if [[ installinginno -eq 1 ]]; then

	sudo $add32bitcommand && sudo $packagemanager $updatecommand && sudo $packagemanager $installcommand $bit32winepkg
	cd $initdir
	echo "----"
	echo "----"
	echo "----"
	echo "----"
	echo "Downloading Inno Setup 6 Windows binary installer"
	echo "----"
	echo "----"
	echo "----"
	echo "----"
	mkdir innosetup
	cd innosetup
	wget https://jrsoftware.org/download.php/is.exe
	wine ./is.exe
fi

echo "----"
echo "----"
echo "----"
echo "----"
echo "All set up. You can now run build.sh"
echo "If you want to cross-compile, build.sh handles that automatically"
echo "If you want to cross-compile without build-sh:"
echo "  Just run 'make windows' in the BookThief or Liesel source tree"

cd $initdir

exit 0
