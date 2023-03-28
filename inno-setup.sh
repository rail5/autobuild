#!/bin/bash

# For restoring the CWD
initdir=`pwd`

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
