#!/usr/bin/with-contenv sh

# Run the given command as PHP user, do not `su php` when not needed
COMMAND=$1

if [ -z "${COMMAND}" ]; then
	return
fi

if [ $(id -un) = 'php' ]; then
  eval "$COMMAND" 2>&1
else
  su php -s "/bin/ash" -c "$COMMAND" 2>&1
fi
