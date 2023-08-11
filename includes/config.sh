#!/usr/bin/env sh

## Configuration file
## This file contains the settings for autobuild to use

# SYSTEM

host_architecture="amd64"
debian_distribution="bullseye"


# PACKAGES

## Key -> Value
## Package name -> Git URL
declare -A packages
packages=()

## Set your packages & their Git URLs here
## as: packages+=(["package-name"]="https://link.to/repository.git")
packages+=(["polonius"]="https://github.com/rail5/polonius.git")
packages+=(["liesel"]="https://github.com/rail5/liesel.git")
packages+=(["bookthief"]="https://github.com/rail5/bookthief.git")
packages+=(["ocrshot"]="https://github.com/rail5/ocrshot.git")
packages+=(["randomtext"]="https://github.com/rail5/randomtext.git")
packages+=(["evolution-notify"]="https://github.com/rail5/evolution-notify.git")
packages+=(["stepgrampa"]="https://github.com/rail5/stepgrampa.git")


# BUILD FARM

## Stuff goes here

## SSH connection into them
SSHPASSWORD="debianpassword"
SSHUSER="debian"
SSHPORT="22222"

# GITHUB

## GitHub info: Username and Access Token
## This user should be the owner of the Git repositories
OWNER="rail5"
ACCESS_TOKEN="$(gpg -d /etc/git/github-token.gpg 2>/dev/null)"

## Location of the Debian Repo we may push to
## This script can push to a Debian Repository hosted on GitHub pages (or similar)
git_debianrepo="https://github.com/rail5/ppa.git"
local_repodirectory="repo"