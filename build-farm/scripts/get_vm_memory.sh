#!/usr/bin/env bash

function get_vm_memory() {
	TOTAL_SYSTEM_MEMORY=$(free -m | tail -n 2 | head -n 1 | awk '{print $2}')

	MEMORY_OPTIONS=(1024 2048 4096 8192 16384 32768 65536)

	MEMORY_WE_WILL_USE=$(expr $TOTAL_SYSTEM_MEMORY / 8) # We want to only use approx. 1/8 of the total available memory on the system for the VM. 1/8th is chosen arbitrarily. The point is that we don't want to just eat all of the user's memory.
	MEMORY_WE_WILL_USE=$(( MEMORY_WE_WILL_USE > 1024 ? MEMORY_WE_WILL_USE : 1024 )) # Debian, however, should really have at least a gig. The manual says 780MB strict minimum.

	for memory_amount in "${MEMORY_OPTIONS[@]}"; do
		if [[ $memory_amount -ge $MEMORY_WE_WILL_USE ]]; then
			MEMORY_WE_WILL_USE=$memory_amount
			break
		fi
	done
	
	echo $MEMORY_WE_WILL_USE
}
