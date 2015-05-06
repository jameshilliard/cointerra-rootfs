#!/bin/sh

if [ ! -f /opt/no_restart_cgminer ] ; then
  if ! pidof cgminer > /dev/null; then
    echo "restart cgminer"
    /etc/init.d/S99cgminer restart
  fi
fi
