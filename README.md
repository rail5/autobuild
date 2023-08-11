## Universalis branch

Development branch

# autobuild

Automatically build & distribute Debian packages

## What does it do?

This script will:

  - Download Debian package sources from a git repo
  - Build them on an x86_64/amd64 host machine
  - Launch a virtual QEMU build-farm & build equivalents on other architectures
  - Push these packages to a Debian Repository hosted on GitHub Pages (or similar) *(assuming you have write access to one)*
  - Create GitHub release pages *(again, assuming you have ownership of the repos they came from)*

## How do I use it?

### Set-up

You should begin by editing the **CONFIG** file *(includes/config.sh)*. This file tells autobuild where to find your package sources & how to distribute them once they're build.

**AFTER** you've edited the CONFIG file, run the setup script:

```
./setup.sh
```

You only have to do this **once.**

### Usage

```
./build.sh
```

Auto downloads package source code from GitHub, and builds (& distributes) using that source code.

With no arguments given, it runs in **interactive mode**, and will ask you what to do each step of the way.

To make it completely automatic, provide arguments. The basic run-down is as follows:

```
./build.sh --package my-debian-package --package my-other-debian-package --github-page
```

The above example will build your packages *"my-debian-package"* and *"my-other-debian-package"* (as you've set them up in the CONFIG file) and then publish them to GitHub Release pages.

See `./build.sh --help` or `./build.sh -h` for a list of options and how to use them.
