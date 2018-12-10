# Dime Migration #

This script connects to the old and the new Dime database and copies the data from the old to the new database. For convenience, we use the Eloquent ORM from the Laravel / Lumen project.

Some of the entities have major changes in terms of table name, table attribute or data types. The migration script takes care of this as well.
## Installation

- Clon the repository: ``git clone https://github.com/stiftungswo/DimeMigration``
- Install the composer dependencies: ``cd DimeMigration && composer install``

## Configuration

You need a connection to both the old and the new Dime database. The connection to those database are configurable through a .env file in the root folder of the project. Have a look at .env.example which things need to be configured and create your own .env or rename .env.example to .env if you run both databases with the same settings.
## Usage

Run the migration with the following command: `` php MigrateDime.php``
