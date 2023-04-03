# autobuild

Scripts to automatically build & distribute Debian packages

This script is by default configured to build *my* packages, but by changing a view variables at the top of **build.sh**, it could be used for any Debian packages hosted in a Git repo

This script will:

  - Download sources from a git repo
  - Compile them on an x86_64/amd64 host machine
  - Launch a virtual QEMU build-farm & build equivalents on Debian Stable i386 & arm64
  - Push these packages to a Debian Repository hosted on GitHub Pages (or similar) *(assuming you have write access to one)*
  - Create GitHub release pages *(again, assuming you have ownership of the repos they came from)*

This script is by default configured to build my own packages, including:
  - Liesel
  - BookThief
  - Polonius
  - OCRShot
  - RandomText

## Running

```
chmod +x build.sh
./build.sh
```
Auto downloads package source code from GitHub, and builds (& distributes) using that source code
 
## Setting up

```
chmod +x machine-setup.sh
./machine-setup.sh
```

Sets up the build environment (compilers, cross-compilers, dependencies, et al)
