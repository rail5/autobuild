## Universalis branch

This is a **development** branch. The idea is to expand autobuild to make it more universal & usable by all, not only for my packages. Once they're ready, these changes will be pushed into *main*.

Planned ideas:

  - One single config file *(Users should not have to mess with any of the shell scripting in order to configure this to build and distribute their own packages)*
  - More flexible build-farm virtual machines *(Users should be able to build custom build-farm VMs, in any combination they want, and to build each package on one, some, or all of them as they choose)*
  - Non-interactive mode *(It should be possible to run the script with some set options so that it runs without waiting for user input throughout the process)*
  - POSIX-compliant shell *(Any and all BASH-isms should be removed so that this will run on as many different shells as possible)*

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
