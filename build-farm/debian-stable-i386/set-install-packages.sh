#!/bin/bash

if [[ $# -eq 0 ]]; then
	echo "Usage:"
	echo "  set-install-packages.sh package1 package2 package3"
	echo "    e.g:"
	echo "  set-install-packages.sh build-essential gcc g++ make git wget tar curl"
	echo "After setting, you can run 'make image'"
	
else
	originalstring="d-i pkgsel/include string sudo"
	replacementstring="d-i pkgsel/include string sudo $@"
	sed -i "s@$originalstring@$replacementstring@" preseed.cfg
fi
