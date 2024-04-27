#!/bin/sh
port=$1
until (telnet localhost "$port"); do sleep 5; done
exec "exit"
