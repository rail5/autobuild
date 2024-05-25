#!/usr/bin/env bash

## Including all of the scripts in this folder

SCRIPT_DIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &> /dev/null && pwd)

. "$SCRIPT_DIR/get_random_free_port.sh"
. "$SCRIPT_DIR/get_vm_memory.sh"
. "$SCRIPT_DIR/boot_vm.sh"
. "$SCRIPT_DIR/install_vm.sh"