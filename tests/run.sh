#!/bin/sh
set -e

COOKIES=/tmp/cookies

fetch() {
  PHP_VERSION_NO_DOT=$(echo "$2" | sed "s/\.//")
  curl --silent --show-error --cookie-jar "$COOKIES" --cookie "$COOKIES" "http://php$PHP_VERSION_NO_DOT/tests/www/$1"
}

reset_cookies() {
  rm -f "$COOKIES"
}

run_tests() {
  HANDLER=$1
  PHP_VERSION=$2

  echo "Running tests using '$HANDLER' handler, PHP Version $PHP_VERSION..."

  reset_cookies
  fetch "write.php?handler=$HANDLER&key=animal&value=dog" $PHP_VERSION
  ANIMAL=$(fetch "read.php?handler=$HANDLER&key=animal" $PHP_VERSION)
  test "${ANIMAL}" = dog
  echo "✅ write/read"

  reset_cookies
  fetch "write.php?handler=$HANDLER&key=animal&value=dog" $PHP_VERSION
  fetch "destroy.php?handler=$HANDLER" $PHP_VERSION
  ANIMAL=$(fetch "read.php?handler=$HANDLER&key=animal" $PHP_VERSION)
  test "${ANIMAL}" = ""
  echo "✅ write/destroy/read"

  reset_cookies
  fetch "write.php?handler=$HANDLER&key=animal&value=dog" $PHP_VERSION
  fetch "unset.php?handler=$HANDLER&key=animal" $PHP_VERSION
  ANIMAL=$(fetch "read.php?handler=$HANDLER&key=animal" $PHP_VERSION)
  test "${ANIMAL}" = ""
  echo "✅ write/unset/read"

  reset_cookies
  PASS=$(fetch "constructor-should-throw-exception.php?handler=$HANDLER" $PHP_VERSION)
  test "$PASS" = PASS
  echo "✅ constructor-should-throw-exception.php"

  reset_cookies
  ID=$(fetch "id.php?handler=$HANDLER" $PHP_VERSION)
  PASS=$(echo "$ID" | perl -pe 's/^[0-9a-f]{32}$/PASS/')
  test "$PASS" = PASS
  echo "✅ id"

  echo "All tests pass for '$HANDLER' handler on PHP $PHP_VERSION!"
}

run_tests redis 7.4
run_tests php 7.4
run_tests redis 8.1
run_tests php 8.1
run_tests redis 8.2
run_tests php 8.2

