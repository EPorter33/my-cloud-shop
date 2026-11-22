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

Signed-in users can change their own password from `account.php` by entering
their current password and confirming a new password. There is no email-based
recovery flow; a user who has forgotten their password needs an administrator
to help them.

## Demo shop

Signed-in users can browse products in `shop.php` and manage a session-based
cart in `cart.php`. Run `product_catalog_schema.sql` against the Azure SQL
database before deploying the catalog changes. Register your account, then
promote it to a catalog administrator by running this query with your email:

```sql
UPDATE dbo.shopusers SET is_admin = 1 WHERE email = N'your-email@example.com';
```

Sign out and back in after making yourself an administrator. Administrators
can add and edit products, set stock, assign fixed categories, and deactivate
products in `manage_products.php`. Product availability is read from the
database, and demo checkout deducts stock in a transaction. It does not collect
payment details or save orders.
