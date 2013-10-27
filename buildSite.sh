#!/bin/bash
apt-get update
apt-get upgrade
apt-get install sudo vim apache2 mysql-server drush php5 php5-common php5-cli
update-alternatives --config editor=vim.basic
