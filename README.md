# Dime Migration #

This script connects to the old and the new Dime database and copies the data from the old to the new database. For convenience, we use the Eloquent ORM from the Laravel / Lumen project.

Some of the entities have major changes in terms of table name, table attribute or data types. The migration script takes care of this as well.
## Installation

- Clon the repository: ``git clone https://github.com/stiftungswo/DimeMigration``
- Install the composer dependencies: ``cd DimeMigration && composer install``

## Configuration

There is currently no option to configure anything (but planned!), so boot up your databases with the following configuration:

Old Dime:
 - Host: 127.0.0.1:3010
 - Username / Password: root / no password
 - Database name: dime
 
 New Dime:
  - Host: 127.0.0.1:33306
  - Username / Password: root / no password
  - Database name: dime

## Usage

Run the migration with the following command: `` php MigrateDime.php``
