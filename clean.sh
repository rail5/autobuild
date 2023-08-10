#!/usr/bin/env sh

## Cleans the builds subdirectory
### By default, asks for confirmation ("Are you sure?")
### To skip this, run with -f

scriptfile=$(readlink -f "$0")
initdir=$(dirname "$scriptfile")

if [ "$1" != "-f" ]; then
	while true; do
		echo "This will delete ALL FILES in:"
		echo "$initdir/builds"
		echo ""
		read -p "Are you sure you want to do this? (y/n) " yn
		case $yn in
			[Yy]* ) break;;
			[Nn]* ) exit 1; break;;
			* ) echo "Answer yes or no"; echo "";;
		esac
	done
fi


cd $initdir/builds
rm -rf ./*

echo "Cleaned builds subdirectory"