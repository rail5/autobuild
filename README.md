# autobuild

Automatically build & distribute Debian packages

![AGPL](https://www.gnu.org/graphics/agplv3-with-text-162x68.png)

## What does it do?

This program can **automatically**:

- Retrieve Debian package sources from a git repo or .tar.gz archive

- Launch a virtual QEMU build-farm & build those packages for various architectures

- Create GitHub/Forgejo release pages for those packages

- Create a Debian Repository to distribute packages (which can be hosted either via GitHub Pages or local web server)

- Push those packages to said Debian Repository

Autobuild is also used to build & distribute itself.

## How do I use it?

### Installation

Autobuild can be easily installed via the [deb.rail5.org](https://deb.rail5.org) repository:

```sh
sudo curl -s -o /etc/apt/trusted.gpg.d/rail5-signing-key.gpg "https://deb.rail5.org/rail5-signing-key.gpg"
sudo curl -s -o /etc/apt/sources.list.d/rail5.list "https://deb.rail5.org/rail5.list"
sudo apt update
sudo apt install autobuild
```

### Set-up

After installing, run `sudo autobuild -s` to complete set-up.

Your **CONFIG** file can be found at `/var/autobuild/config.toml`

### Command-line usage

```
autobuild --amd64 --i386 --arm64 --package my-debian-package --package my-other-debian-package --github-page
```

```
autobuild -123 -p https://github.com/user/my-debian-package -p /path/to/my-other-debian-package.tar.gz -g
```

The above examples will build your packages *"my-debian-package"* and *"my-other-debian-package"* and then publish them to GitHub Release pages.

```
autobuild --local -p my-debian-package -o /home/user/
```

The above example will build your package *locally* (without using the virtual build farm) and save the resulting build in /home/user

See `autobuild --help`, `autobuild -h`, or `man autobuild` for a list of options and how to use them.

## Autobuild-web

A web interface is provided by the **autobuild-web** package:

```sh
sudo apt install autobuild-web
```

After installing, it will be available on your web-server at **http://your-domain-or.ip/autobuild**

| Desktop                                                                                                               | Mobile                                                                                                                        |
| --------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| <img title="" src="https://rail5.org/autobuild/autobuild-web-build-menu.png" alt="build-menu" width="800">            | <img title="" src="https://rail5.org/autobuild/autobuild-web-mobile-build-menu.jpeg" alt="build-menu" width="300">            |
| <img src="https://rail5.org/autobuild/autobuild-web-log-build-successful.png" title="" alt="log-success" width="800"> | <img src="https://rail5.org/autobuild/autobuild-web-mobile-log-build-successful.jpeg" title="" alt="log-success" width="300"> |
| <img src="https://rail5.org/autobuild/autobuild-web-repository-menu.png" title="" alt="repo-menu" width="800">        | <img src="https://rail5.org/autobuild/autobuild-web-mobile-repository-menu.jpeg" title="" alt="repo-menu" width="300">        |

