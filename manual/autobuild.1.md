% autobuild(1) Version 3.0 | Manual for autobuild
# NAME
autobuild \- Automatically build and distribute Debian packages

# SYNOPSIS
`autobuild --amd64 --i386 --arm64 -p package-name -p another-package`

`autobuild --amd64 --github-page -p https://github.com/user/package.git`

`autobuild --i386 --debian-repo default --forgejo-page -p /path/to/package.tar.gz`

# DESCRIPTION
Autobuild automatically retrieves Debian source packages (via Git or local tar.gz archive), builds them on a virtual machine build-farm for multiple architectures, and distributes them. It can save the built package locally, distribute them to a GitHub/Forgejo Release Page, and distribute them to a Git-based Debian Repository with reprepro.

The configuration file can be found at /var/autobuild/config.toml. This file may be more conveniently edited via the autobuild -s command.

# OPTIONS

## GENERAL OPTIONS
-p, \--package

    Add a package to the build list. The argument can be:
    - The name of a package in the config file
    - A valid Git URL
    - A local path to a .tar.gz archive

-o, \--output

    Specify directory to save built package files
    (default: current working directory)

-b, \--bell

    Ring a bell when finished

-l, \--list

    List packages present in the config file and quit

-L, \--log

    Write output to a specified log file/directory instead of
    to the terminal.
    If the argument is a directory, create a new file with the
    name of the Job ID.

-r, \--remove-old-builds

    Remove a given subdirectory under /var/autobuild/builds, and quit
    If the given argument is 'all', then remove everything under
    /var/autobuild/builds

-s, \--setup

    Run the setup program (must be run as root)
    The setup program can automatically install (or reinstall) the
    build farm

## BUILD FARM OPTIONS
-0, \--local

    Build packages on the local machine (do not use the build farm)

-1, \--amd64

    Build packages on the amd64 build farm VM

-2, \--i386

    Build packages on the i386 build farm VM

-3, \--arm64

    Build packages on the arm64 build farm VM

-u, \--upgrade

    Upgrade Build Farm VMs and exit
    If --amd64, --i386, and/or --arm64 are specified, upgrade only those VMs which were specified

-n, \--no-upgrade

    Do not upgrade Build Farm VMs before building packages

## DISTRIBUTION OPTIONS
-g, \--github-page

    Create release pages for the built packages' GitHub repositories
    Your GitHub credentials must be stored in the config file

-f, \--forgejo-page

    Create release pages for the built packages' Forgejo repositories
    Your Forgejo credentials (and instance URL) must be stored in the
    config file

-d, \--debian-repo

    Distribute built packages to a Git-based Debian Repository via
    reprepro
    This repository must be configured via the config file
    The argument should be the name of a Debian repo to push to

## SIGNING KEY OPTIONS
-C, \--create-signing-key

    Create a new package signing key

-D, \--delete-signing-key

    Delete an existing package signing key

-E, \--key-email

    Specify email address to use for the signing key

-N, \--key-name

    Specify name to use for the signing key

# AUTHOR
rail5 (andrew@rail5.org)
