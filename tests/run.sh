#!/bin/sh
set -e

COOKIES=/tmp/cookies

fetch() {
  curl --silent --show-error --cookie-jar "$COOKIES" --cookie "$COOKIES" "http://php/tests/www/$1"
}

reset_cookies() {
  rm -f "$COOKIES"
}

run_tests() {
  HANDLER=$1

  echo "Running tests using '$HANDLER' handler..."

  reset_cookies
  fetch "write.php?handler=$HANDLER&key=animal&value=dog"
  ANIMAL=$(fetch "read.php?handler=$HANDLER&key=animal")
  test "${ANIMAL}" = dog
  echo "✅ write/read"

  reset_cookies
  fetch "write.php?handler=$HANDLER&key=animal&value=dog"
  fetch "destroy.php?handler=$HANDLER"
  ANIMAL=$(fetch "read.php?handler=$HANDLER&key=animal")
  test "${ANIMAL}" = ""
  echo "✅ write/destroy/read"

  reset_cookies
  fetch "write.php?handler=$HANDLER&key=animal&value=dog"
  fetch "unset.php?handler=$HANDLER&key=animal"
  ANIMAL=$(fetch "read.php?handler=$HANDLER&key=animal")
  test "${ANIMAL}" = ""
  echo "✅ write/unset/read"

  reset_cookies
  PASS=$(fetch "constructor-should-throw-exception.php?handler=$HANDLER")
  test "$PASS" = PASS
  echo "✅ constructor-should-throw-exception.php"

  reset_cookies
  ID=$(fetch "id.php?handler=$HANDLER")
  PASS=$(echo "$ID" | perl -pe 's/^[0-9a-f]{32}$/PASS/')
  test "$PASS" = PASS
  echo "✅ id"

  echo "All tests pass for '$HANDLER' handler!"
}

run_tests php
run_tests redis
