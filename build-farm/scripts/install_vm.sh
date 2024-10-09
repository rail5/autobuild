#!/usr/bin/env bash

function download_vm_image() {
	if [[ $# != 2 ]]; then
		echo "bad args to dowload_vm_image" && exit
	fi

	local ARCH="$1" DIRECTORY="$2"
	cd "$DIRECTORY" || (echo ""; echo "download_vm_image: Could not cd into $DIRECTORY"; exit)

	temporary_file=$(mktemp -p ./)
	curl -L -o "$temporary_file" https://www.debian.org/CD/netinst/

	image_url=$(grep -o -P -e "https://cdimage.debian.org/debian-cd/current/$ARCH/iso-cd/debian.*?netinst.iso" "$temporary_file" | head -n1)

	curl -L -o "debian-$ARCH-netinst.iso" "$image_url"
	rm -f "$temporary_file"
}

function preseed_vm_image() {
	if [[ $# != 2 ]]; then
		echo "bad args to preseed_vm_image" && exit
	fi

	local ARCH="$1" DIRECTORY="$2" ARCHFOLDER="amd"

	if [[ "$ARCH" == "amd64" ]]; then
		ARCHFOLDER="amd"
	elif [[ "$ARCH" == "i386" ]]; then
		ARCHFOLDER="386"
	elif [[ "$ARCH" == "arm64" ]]; then
		ARCHFOLDER="a64" # Really unhappy with how format-inconsistent these names are in Debian
	fi

	cd "$DIRECTORY" || (echo ""; echo "preseed_vm_image: Could not cd into $DIRECTORY"; exit)

	# Unpack the image and set write permissions
	rm -rf "./tmp"
	mkdir tmp

	if [[ "$ARCH" == "arm64" ]]; then
		mkdir -p "tmp/isolinux" # arm64 needs a few extra accommodations
		mkdir -p "tmp/boot/grub"
	fi

	bsdtar -C tmp -xf "debian-$ARCH-netinst.iso"
	chmod -R +w tmp

	# Create a minimal boot config - no menu, no prompt
	# isolinux (BIOS)
	sed -e "s/<ARCH>/$ARCHFOLDER/g" \
		-e "s/<CONSOLE>/console=ttyS0,115200n8/g" \
		"../templates/isolinux.cfg.template" > "tmp/isolinux/isolinux.cfg"
	
	# grub (UEFI)
	sed -e "s/<ARCH>/$ARCHFOLDER/g" \
		-e "s/<CONSOLE>/console=ttyS0,115200n8/g" \
		"../templates/grub.cfg.template" > "tmp/boot/grub/grub.cfg"
	
	# Write the preseed file to initrd
	gunzip "tmp/install.$ARCHFOLDER/initrd.gz"
	echo preseed.cfg | cpio -H newc -o -A -F "tmp/install.$ARCHFOLDER/initrd"
	gzip "tmp/install.$ARCHFOLDER/initrd"

	# Recreate the MD5 sums of all files
	find "tmp/" -type f -exec md5sum {} \; > "tmp/md5sum.txt"

	# Create the ISO and fix MBR for USB boot
	if [[ "$ARCH" == "arm64" ]]; then
		genisoimage -V "Debian-stable-$ARCH-headless" \
			-r -J -e boot/grub/efi.img \
			-no-emul-boot \
			-o "debian-$ARCH-netinst-hl.iso" tmp

			# Set up the EFI firmware necessary to run
			truncate -s 64m varstore.img
			truncate -s 64m efi.img
			dd if=/usr/share/qemu-efi-aarch64/QEMU_EFI.fd of=efi.img conv=notrunc
	else
		genisoimage -V "Debian-stable-$ARCH-headless" \
			-r -J -b isolinux/isolinux.bin -c isolinux/boot.cat \
			-no-emul-boot -boot-load-size 4 -boot-info-table \
			-eltorito-alt-boot \
			-e boot/grub/efi.img \
			-no-emul-boot \
			-o "debian-$ARCH-netinst-hl.iso" tmp
		isohybrid --uefi "debian-$ARCH-netinst-hl.iso"
	fi
	rm -rf "./tmp"
}

function install_vm() {
	if [[ $# != 2 ]]; then
		echo "bad args to preseed_vm_image" && exit
	fi

	local ARCH="$1" ARCH_DIRECTORY="$2" ARCH_STRING=""
	local OPTIONAL_EXTRA_COMMAND="-cdrom debian-$ARCH-netinst-hl.iso"

	if [[ "$ARCH" == "amd64" ]]; then
		ARCH_STRING="x86_64"
	elif [[ "$ARCH" == "i386" ]]; then
		ARCH_STRING="i386"
	elif [[ "$ARCH" == "arm64" ]]; then
		ARCH_STRING="aarch64"
		OPTIONAL_EXTRA_COMMAND="-drive file=./debian-$ARCH-netinst-hl.iso,id=cdrom,if=none,media=cdrom -device virtio-scsi-device -device scsi-cd,drive=cdrom,bootindex=1"
	fi

	IMAGE="$ARCH_DIRECTORY/image.qcow"

	qemu-img create -f qcow2 "$IMAGE" 20G

	SSHPORT=$(get_random_free_port)
	TELNETPORT=$(get_random_free_port)

	while [[ "$TELNETPORT" == "$SSHPORT" ]]; do
		TELNETPORT=$(get_random_free_port)
	done

	boot_vm_nodisplay "$ARCH_STRING" "$IMAGE" "$SSHPORT" "$TELNETPORT" "$OPTIONAL_EXTRA_COMMAND"
}