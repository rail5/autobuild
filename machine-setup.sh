#!/bin/bash

## Environment variables ##
packagemanager="apt"
installcommand="install"
updatecommand="update"

## Needed if we're installing Inno Setup later
add32bitcommand="dpkg --add-architecture i386"
bit32winepkg="wine32"

# Build Dependencies
basepkgslist="build-essential gcc g++ make git wget tar"

bookthiefdeps="fpc-3.2.0 lazarus lcl-2.0 lcl-utils-2.0 fp-units-misc-3.2.0"
lieseldeps="graphicsmagick-libmagick-dev-compat libmagick++-6-headers libfontconfig1-dev libpoppler-cpp-dev libhpdf-dev"

packagingdeps="devscripts wine wine64 php-cli"

mxedeps="autoconf automake autopoint bash bison bzip2 flex g++ g++-multilib gettext git gperf intltool libc6-dev-i386 libgdk-pixbuf2.0-dev libltdl-dev libssl-dev libtool-bin libxml-parser-perl lzip make openssl p7zip-full patch perl python ruby sed unzip wget xz-utils python3-mako"

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
echo "This script must be run as ROOT"

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

echo "Installing build dependencies..."

$packagemanager $updatecommand

$packagemanager $installcommand $basepkgslist $bookthiefdeps $lieseldeps $packagingdeps

echo "----"
echo "----"
echo "----"
echo "----"

echo "Build dependencies installed"
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
$packagemanager $installcommand $mxedeps

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

	git clone https://github.com/mxe/mxe.git

	cd mxe

	echo "MXE_PLUGIN_DIRS := plugins/gcc11" > ./settings.mk
	echo "MXE_TARGETS := x86_64-w64-mingw32.static" >> ./settings.mk

	echo "----"
	echo "Building the GCC cross-compiler"
	echo "----"

	make gcc -j 8

	echo "----"
	echo "Adding /opt/mxe/bin and /opt/mxe/usr/bin/ to \$PATH via /etc/profile"
	echo "----"
	echo "" >> /etc/profile
	echo "export PATH=/opt/mxe/usr/bin:/opt/mxe/bin:\$PATH" >> /etc/profile

	export PATH=/opt/mxe/usr/bin:/opt/mxe/bin:$PATH

	echo "----"
	echo "Cross-compiling Liesel's dependencies"
	echo "----"

	make graphicsmagick poppler libharu -j 8
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
	mkdir fpc
	cd fpc

	wget https://deac-ams.dl.sourceforge.net/project/freepascal/Source/3.2.0/fpc-3.2.0.source.tar.gz

	echo "--"
	echo "Unpacking"
	echo "--"

	tar -xvzf fpc-3.2.0.source.tar.gz

	cd fpc-3.2.0

	export FPCVER="3.2.0"

	echo "--"
	echo "Compiling FPC win64 cross-compiler for BookThief"
	echo "--"
	make clean all OS_TARGET=win64 CPU_TARGET=x86_64

	make crossinstall OS_TARGET=win64 CPU_TARGET=x86_64 INSTALL_PREFIX=/usr

	ln -sf /usr/lib/fpc/3.2.0/ppcrossx64 /usr/bin/ppcrossx64

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
		echo "" >> /etc/fpc.cfg
		echo "-Fu/usr/lib/fpc/\$fpcversion/units/\$fpctarget/*" >> /etc/fpc.cfg
		echo "Line added to bottom of /etc/fpc.cfg"
	fi

fi

installinginno=0

echo "---"
echo "Inno Setup 6 is necessary in order to build the Windows Installer Package"
echo "This script can download the Inno Setup 6 installer, and open it via Wine"
echo "This requires installing Wine's 32 bit libraries"
echo "You also need a functioning X-Server, since Inno Setup runs as a Graphical installer"
echo "If you don't know what that means, you probably have one"
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

	$add32bitcommand && $packagemanager $updatecommand && $packagemanager $installcommand $bit32winepkg
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
echo "  For BookThief: just run 'make windows'"
echo "  For Liesel: run 'make windows CROSS=x86_64-w64-mingw32.static-"

cd $initdir

exit 0
