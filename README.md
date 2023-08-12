# autobuild

Automatically build & distribute Debian packages

## What does it do?

This script will:

  - Download Debian package sources from a git repo
  - Build them on an x86_64/amd64 host machine
  - Launch a virtual QEMU build-farm & build equivalents on other architectures
  - Push these packages to a Debian Repository hosted on GitHub Pages (or similar) *(assuming you have write access to one)*
  - Create GitHub release pages *(again, assuming you have ownership of the repos they came from)*

Autobuild is also used to build & distribute *itself*.

## How do I use it?

### Installation

Autobuild can be easily installed via the [deb.rail5.org](https://deb.rail5.org) repository:

```
sudo curl -s -o /etc/apt/trusted.gpg.d/rail5.gpg "https://deb.rail5.org/rail5.gpg"
sudo curl -s -o /etc/apt/sources.list.d/rail5.list "https://deb.rail5.org/debian/rail5.list"
sudo apt update
sudo apt install autobuild
```

### Set-up

After installing, run `autobuild -c` to edit your configuration, followed by `autobuild -s` to complete set-up based on that configuration.

Your **CONFIG** file (edited with `autobuild -c`) is where you will tell autobuild where to find your packages, as well as other settings

### Usage

```
autobuild
```

With no arguments given, it runs in **interactive mode**, and will ask you what to do each step of the way.

To make it completely automatic, provide arguments. The basic run-down is as follows:

```
autobuild --package my-debian-package --package my-other-debian-package --github-page
```

The above example will build your packages *"my-debian-package"* and *"my-other-debian-package"* (as you've set them up in the CONFIG file) and then publish them to GitHub Release pages.

See `autobuild --help`, `autobuild -h`, or `man autobuild` for a list of options and how to use them.
