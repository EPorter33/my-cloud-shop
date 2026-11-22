# My Cloud Shop

## Login and registration

The login and registration pages use the Azure SQL `shopusers` table with
`name`, `email`, and `password` columns. Passwords must be stored as hashes
created by PHP's `password_hash()`; login verifies them with
`password_verify()`.

Configure these environment variables for the PHP web server:

- `SHOP_DB_SERVER` — SQL Server endpoint, for example `tcp:myserver.database.windows.net,1433`
- `SHOP_DB_DATABASE` — database name
- `SHOP_DB_USERNAME` — database login
- `SHOP_DB_PASSWORD` — database password

The PHP SQLSRV extension must be enabled. Configure a unique index on the
`email` column to prevent duplicate accounts, and rotate any database
credentials that were previously stored in PHP source files.
