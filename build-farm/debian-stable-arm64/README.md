# Debian headless/remote installation

Many thanks to [Philipp Pagel](https://github.com/philpagel/debian-headless) for writing the original script that this is based on

The original script seemed to aim for an interactive installation on genuine hardware which did not have peripherals such as a keyboard/monitor etc

This script has been modified to:

  - Have a completely non-interactive, fully-automatic installation
  - Focus on building & running in QEMU, rather than on actual hardware

With these changes, it's usable as the basis for a "build farm" to build packages for multiple distributions/architectures

## This one

This one builds Debian Stable arm64 on an x86_64 host.

## Usage

```
make install-depends

make download

make image

make boot-install
```

After installation, you can run `make boot-run`, and the script will open a terminal window with a telnet connection to your new VM
