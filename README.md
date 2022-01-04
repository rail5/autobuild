# autobuild

Scripts to auto-build (& auto cross-compile) BookThief + Liesel

## Setting up

This script assumes you're running Debian. It could probably work on other systems with heavy modification

```
chmod +x machine-setup.sh
./machine-setup.sh
```

Sets up the build environment (compilers, cross-compilers, dependencies, et al)

## Running

```
chmod +x build.sh
./build.sh
```
Auto downloads Liesel & BookThief source code from GitHub, and (using that source code)

 - Compiles DEB packages
 - Cross-compiles for Win64
 - Creates a Win64 Installer
 - Builds DEB source-only packages
