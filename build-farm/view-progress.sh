#!/bin/sh
sleep 5; telnet localhost 33333
exec "$SHELL"
