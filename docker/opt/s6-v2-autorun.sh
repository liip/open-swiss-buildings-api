#!/usr/bin/env sh

# Configuring S6 to discover services: for each service defined in /etc/s6-overlay/s6-rc.d/
# we create a file with the service's name under `/etc/s6-overlay/s6-rc.d/user/contents.d` to let them auto-run
# this is according to the v2 specification of S6
# Special services names "user" and "user2" are excluded, as handled by S6
find /etc/s6-overlay/s6-rc.d -type d -maxdepth 1 -mindepth 1 ! -name user ! -name user2 -exec basename {} \; \
  | xargs -I{} touch /etc/s6-overlay/s6-rc.d/user/contents.d/{} ;
