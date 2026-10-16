# Minimum version of PHP supported by this project
PHP_VERSION ?= 8.3

# Uncomment and modify for custom extensions. Don't use "Quotes".
PHP_EXTENSIONS ?= mbstring json xdebug ldap pdo-sqlite

# Uncomment this and set it to something reasonable, otherwise you may have problems with extensions/php versions
# due to the same image being used across multiple projects
DOCKER_IMAGE_NAME = laminas/authentication

# Includes _everything_ supplied by the internal tooling lib
include vendor/laminas/internal-tooling/Makefile
