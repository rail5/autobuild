include Makevars
.PHONY: help install-depends config download image unpack bootconfig preseed md5 iso qemu-bios qemu-uefi usb FAT clean maintainer-clean

help:
	@echo
	@echo "Usage:"
	@echo
	@echo "  make install-depends       Install dependencies"
	@echo "  make config                Edit configuration (Makevars)"
	@echo "  make download              download *latest* Debian netinst image"
	@echo "  make example-preseed.cfg   download example-preseed.cfg from Debian"
	@echo "  make image                 Build the ISO image"
	@echo "  make boot-install          Boot ISO image in QEMU and install"
	@echo "  make boot-run              Boot installed image in QEMU and open a telnet connection"
	@echo "  make boot-nodisplay        Boot installed image in QEMU"
	@echo "  make clean                 Clean up temporary files and folders"
	@echo "  make full-clean            Regular clean + Delete ISOs"
	@echo "  make delete-system         full-clean + Delete image.qcow disk image"
	@echo
	@echo "See README.md for details"
	@echo

install-depends:
	sudo apt-get install \
		libarchive-tools syslinux syslinux-utils cpio genisoimage \
		coreutils qemu-system qemu-system-arm qemu-utils util-linux xz-utils \
		qemu-efi-aarch64 libguestfs-tools

config:
	editor Makevars

.ONESHELL:
download:
	set -e
	TMPFILE=`mktemp -p ./`
	wget -O $$TMPFILE https://www.debian.org/CD/netinst/
	IMGURL=`grep -o -P -e "https://cdimage.debian.org/debian-cd/current/${ARCH}/iso-cd/debian.*?netinst.iso" $$TMPFILE | head -n1`
	FILENAME=`echo "$$IMGURL" | grep -o -P -e "/debian-[0-9].*?-netinst.iso" | cut -c2-`
	wget -N $$IMGURL
	rm -f $$TMPFILE
	mv $$FILENAME debian-stable-arm64-netinst.iso

example-preseed.cfg:
	wget -N -O $@ https://www.debian.org/releases/stable/example-preseed.txt

image: unpack bootconfig preseed md5sums iso setupefi

unpack:
	# Unpack the image to the folder and set write permissions.
	rm -rf ${TMP}
	mkdir ${TMP}
	mkdir -p ${TMP}/isolinux
	mkdir -p ${TMP}/boot/grub
	bsdtar -C ${TMP} -xf ${SOURCE}
	chmod -R +w ${TMP}

bootconfig: 
	# Create a minimal boot config – no menu, no prompt
	# isolinux (BIOS)
	sed -e "s/<ARCH>/${ARCHFOLDER}/g" \
		-e "s/<CONSOLE>/console=${CONSOLE}/g" \
		${ISOLINUX_CFG_TEMPLATE} > ${TMP}/isolinux/isolinux.cfg
	# grub (UEFI)
	sed -e "s/<ARCH>/${ARCHFOLDER}/g" \
		-e "s/<CONSOLE>/console=${CONSOLE}/g" \
		${GRUB_CFG_TEMPLATE} > ${TMP}/boot/grub/grub.cfg

preseed: preseed.cfg
	# Write the preseed file to initrd.
	gunzip ${TMP}/install.${ARCHFOLDER}/initrd.gz
	echo preseed.cfg | cpio -H newc -o -A -F ${TMP}/install.${ARCHFOLDER}/initrd
	gzip ${TMP}/install.${ARCHFOLDER}/initrd

md5sums:
	# Recreate the MD5 sums of all files.
	find ${TMP}/ -type f -exec md5sum {} \; > ${TMP}/md5sum.txt

iso: ${TMP}
	# Create ISO and fix MBR for USB boot.
	genisoimage -V ${LABEL} \
		-r -J -e boot/grub/efi.img \
		-no-emul-boot \
		-o ${TARGET} ${TMP}

setupefi:
	# Set up the EFI firmware necessary to run the machine
	truncate -s 64m varstore.img
	truncate -s 64m efi.img
	dd if=/usr/share/qemu-efi-aarch64/QEMU_EFI.fd of=efi.img conv=notrunc

boot-install: image.qcow
	# boot image in qemu (UEFI mode)
	@echo
	@echo "Once the installer has launched networking you can log in:\n"
	@echo "    ssh installer@localhost -p22222\n"
	@echo "It may take a few minutes for the installer to get to that point.\n"
	@echo "Alternatively connect to the serial console:\n"
	@echo "    telnet localhost 33333\n"
	@x-terminal-emulator -e /usr/share/autobuild/build-farm/view-progress.sh &
	${QEMU} -m 2048 \
		-machine virt \
		-cpu cortex-a53 \
		-smp 2 \
		-drive if=pflash,format=raw,file=efi.img,readonly=on \
		-drive if=pflash,format=raw,file=varstore.img \
		-object rng-random,filename=/dev/urandom,id=rng0 \
		-device virtio-rng-pci,rng=rng0 \
		-accel tcg \
		-net user,hostfwd=tcp::22222-:22 \
		-net nic \
		-drive if=virtio,file=image.qcow,format=qcow2,id=hd \
		-drive file=./debian-stable-arm64-netinst-hl.iso,id=cdrom,if=none,media=cdrom -device virtio-scsi-device -device scsi-cd,drive=cdrom \
		-serial telnet:localhost:33333,server,nowait

boot-run:
	@x-terminal-emulator -e /usr/share/autobuild/build-farm/view-progress.sh &
	${QEMU} -m 2048 \
		-machine virt \
		-cpu cortex-a53 \
		-smp 2 \
		-drive if=pflash,format=raw,file=efi.img,readonly=on \
		-drive if=pflash,format=raw,file=varstore.img \
		-object rng-random,filename=/dev/urandom,id=rng0 \
		-device virtio-rng-pci,rng=rng0 \
		-accel tcg \
		-net user,hostfwd=tcp::22222-:22 \
		-net nic \
		-drive if=virtio,file=image.qcow,format=qcow2,id=hd \
		-serial telnet:localhost:33333,server,nowait

boot-notelnet:
	${QEMU} -m 2048 \
		-machine virt \
		-cpu cortex-a53 \
		-smp 2 \
		-drive if=pflash,format=raw,file=efi.img,readonly=on \
		-drive if=pflash,format=raw,file=varstore.img \
		-object rng-random,filename=/dev/urandom,id=rng0 \
		-device virtio-rng-pci,rng=rng0 \
		-accel tcg \
		-net user,hostfwd=tcp::22222-:22 \
		-net nic \
		-drive if=virtio,file=image.qcow,format=qcow2,id=hd \
		-serial telnet:localhost:33333,server,nowait

boot-nodisplay:
	${QEMU} -m 2048 \
		-machine virt \
		-cpu cortex-a53 \
		-smp 2 \
		-drive if=pflash,format=raw,file=efi.img,readonly=on \
		-drive if=pflash,format=raw,file=varstore.img \
		-object rng-random,filename=/dev/urandom,id=rng0 \
		-device virtio-rng-pci,rng=rng0 \
		-accel tcg \
		-net user,hostfwd=tcp::22222-:22 \
		-net nic \
		-drive if=virtio,file=image.qcow,format=qcow2,id=hd \
		-serial telnet:localhost:33333,server,nowait \
		-nographic

image.qcow:
	# Create a virtual disk for QEMU.
	qemu-img create -f qcow2 $@ 32G

clean:
	rm -rf ${TMP}
	rm -f example-preseed.cfg

full-clean:
	rm -rf ${TMP}
	rm -f example-preseed.cfg
	rm -f ${SOURCE}
	rm -f ${TARGET}

delete-system:
	rm -rf ${TMP}
	rm -f example-preseed.cfg
	rm -f ${SOURCE}
	rm -f ${TARGET}
	rm -f image.qcow
	rm -f varstore.img
	rm -f efi.img
