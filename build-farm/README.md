# Build Farm

Many thanks to [Philipp Pagel](https://github.com/philpagel/debian-headless) for writing the original script that this is based on

The original script seemed to aim for an interactive installation on genuine hardware which did not have peripherals such as a keyboard/monitor etc

Here, his script has been modified to:

  - Have a completely non-interactive, fully-automatic installation
  - Focus on building & running in QEMU, rather than on actual hardware

With these changes, it's usable as the basis for a "build farm" to build packages for multiple distributions/architectures

## What's included

So far, VMs of:

  - Debian Stable amd64
  - Debian Stable i386
  - Debian Stable arm64

## Usage

The build farm is built by the `autobuild -s` setup script.
