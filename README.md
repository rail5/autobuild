# autobuild

Automatically build & distribute Debian packages

![AGPL](https://www.gnu.org/graphics/agplv3-with-text-162x68.png)

## What does it do?

This program will **automatically**:

- Download Debian package sources from a git repo

- Launch a virtual QEMU build-farm & build those packages for various architectures

- Create GitHub release pages for those packages

- Push those packages to a Debian Repository hosted on GitHub Pages

Autobuild is also used to build & distribute itself.

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

After installing, run `autobuild -c` to edit your configuration, and `autobuild -s` to complete set-up.

Your **CONFIG** file (edited with `autobuild -c`) is where you will tell autobuild where to find your packages, as well as other settings

### Usage

```
autobuild --amd64 --i386 --arm64 --package my-debian-package --package my-other-debian-package --github-page
```

```
autobuild -1 -2 -3 -p my-debian-package -p my-other-debian-package -g
```

The above examples will build your packages *"my-debian-package"* and *"my-other-debian-package"* (as you've set them up in the CONFIG file) and then publish them to GitHub Release pages.

```
autobuild --local -p my-debian-package -o /home/user/
```

The above example will build your package *locally* (without using the virtual build farm) and save the resulting build in /home/user

See `autobuild --help`, `autobuild -h`, or `man autobuild` for a list of options and how to use them.
