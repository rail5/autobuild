#!/bin/bash

initdir=$(pwd)

ubuntudist="focal"
debiandist="bullseye"
export ubuntudist="focal"
export debiandist="bullseye"

cd $initdir/build-farm

# i386
cd debian-stable-i386

make boot-nodisplay &

# Wait for VM to come online
sleep 30

# The following commands (until EOF) are passed to the VM via ssh

sshpass -p debianpassword ssh -o "UserKnownHostsFile=/dev/null" -o "StrictHostKeyChecking=no" -tt -p 22222 debian@127.0.0.1 << EOF
mkdir -p /home/debian/build/src
mkdir -p /home/debian/build/pkg

echo "Cleaning build environment"
cd /home/debian/build/pkg
rm -rf ./*
cd /home/debian/build/src
rm -rf ./*

sudo apt update -y

sudo apt upgrade -y


echo "Getting latest sources"
git clone https://github.com/rail5/liesel.git
git clone https://github.com/rail5/bookthief.git

ubuntudist="focal"
debiandist="bullseye"
export ubuntudist="focal"
export debiandist="bullseye"

sed -i "s/$ubuntudist/$debiandist/gi" liesel/debian/changelog
sed -i "s/$ubuntudist/$debiandist/gi" bookthief/debian/changelog

cd /home/debian/build/src/liesel
debuild -us -uc

cd /home/debian/build/src/bookthief
debuild -us -uc

cd /home/debian/build/src

rm -rf ./liesel
rm -rf ./bookthief

cd /home/debian/build/src

tar -cvzf /home/debian/build/pkg/packages.tar.gz ./

exit
EOF

sshpass -p debianpassword scp -o "UserKnownHostsFile=/dev/null" -o "StrictHostKeyChecking=no" -P 22222 -r debian@127.0.0.1:/home/debian/build/pkg/packages.tar.gz $initdir/build-farm/packages/packages.tar.gz

cd $initdir/build-farm/packages
tar -xvzf packages.tar.gz
rm -f packages.tar.gz

cd $initdir

sshpass -p debianpassword ssh -o "UserKnownHostsFile=/dev/null" -o "StrictHostKeyChecking=no" -tt -p 22222 debian@127.0.0.1 << EOF
sudo shutdown now

EOF

echo "Build-farm build completed, packages retrieved"
