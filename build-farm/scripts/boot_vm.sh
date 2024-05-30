#!/usr/bin/env bash

function boot_vm() {

	if [[ $# -lt 6 ]]; then
		echo "bad args to boot_vm"
		exit
	fi

	local QEMU="$1" IMAGE="$2" MEMORY="$3" ACCEL="$4" SSHPORT="$5" TELNET_PORT="$6" OPTIONAL_EXTRA_COMMAND="$7"
	
	IMAGE_DIRECTORY=$(dirname "$IMAGE")
	
	QEMU_COMMAND=""
	
	# I hate this. So much.
	if [[ "$QEMU" == "qemu-system-aarch64" ]]; then
		
		QEMU_COMMAND="$QEMU -m $MEMORY \
			-machine virt \
			-cpu cortex-a53 \
			-smp 2 \
			-drive if=pflash,format=raw,file=$IMAGE_DIRECTORY/efi.img,readonly=on \
			-drive if=pflash,format=raw,file=$IMAGE_DIRECTORY/varstore.img \
			-object rng-random,filename=/dev/urandom,id=rng0 \
			-device virtio-rng-pci,rng=rng0 \
			$ACCEL \
			-net user,hostfwd=tcp::$SSHPORT-:22 \
			-net nic \
			-drive if=virtio,file=$IMAGE_DIRECTORY/image.qcow,format=qcow2,id=hd \
			$OPTIONAL_EXTRA_COMMAND \
			-serial telnet:localhost:$TELNET_PORT,server,nowait \
			-nographic"
	else
		QEMU_COMMAND="$QEMU -m $MEMORY \
			$ACCEL \
			-net user,hostfwd=tcp::$SSHPORT-:22 \
			-net nic \
			-hda $IMAGE \
			-serial telnet:localhost:$TELNET_PORT,server,nowait \
			$OPTIONAL_EXTRA_COMMAND \
			-nographic"
	fi
	
	$QEMU_COMMAND
	
	return $?
}

function boot_vm_nodisplay() {

	if [[ $# -lt 4 ]]; then
		echo "bad args to boot_vm_nodisplay"
		exit
	fi

	local ARCH="$1" IMAGE="$2" SSH_PORT="$3" TELNET_PORT="$4" OPTIONAL_EXTRA_COMMAND="$5"

	BOOT_SCRIPT_DIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &> /dev/null && pwd)

	. "$BOOT_SCRIPT_DIR/../scripts/scripts.sh" # Get some functions we'll be using

	QEMU=qemu-system-$ARCH

	ACCEL_OPTION="-accel kvm"
	MEMORY_TO_USE=$(get_vm_memory)

	# Try with KVM acceleration
	boot_vm "$QEMU" "$IMAGE" "$MEMORY_TO_USE" "$ACCEL_OPTION" "$SSH_PORT" "$TELNET_PORT" "$OPTIONAL_EXTRA_COMMAND" &
	
	qemu_pid=$!
	
	wait $qemu_pid
	
	if [[ $? -ne 0 ]]; then
		# Try without KVM acceleration
		echo "Booting VM with KVM acceleration failed."
		echo "Trying without KVM..."
		ACCEL_OPTION="-accel tcg"
		boot_vm "$QEMU" "$IMAGE" "$MEMORY_TO_USE" "$ACCEL_OPTION" "$SSH_PORT" "$TELNET_PORT" "$OPTIONAL_EXTRA_COMMAND" &
	fi
}
