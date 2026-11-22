-- Run once in the Azure SQL database used by the app.

IF COL_LENGTH('dbo.shopusers', 'is_admin') IS NULL
BEGIN
    ALTER TABLE dbo.shopusers
        ADD is_admin bit NOT NULL
        CONSTRAINT DF_shopusers_is_admin DEFAULT (0);
END;

IF OBJECT_ID(N'dbo.shop_products', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.shop_products (
        product_id int IDENTITY(1,1) NOT NULL
            CONSTRAINT PK_shop_products PRIMARY KEY,
        name nvarchar(150) NOT NULL,
        description nvarchar(1000) NOT NULL
            CONSTRAINT DF_shop_products_description DEFAULT (N''),
        category nvarchar(30) NOT NULL,
        price_pence int NOT NULL,
        stock_quantity int NOT NULL
            CONSTRAINT DF_shop_products_stock DEFAULT (0),
        is_active bit NOT NULL
            CONSTRAINT DF_shop_products_active DEFAULT (1),
        created_at datetime2 NOT NULL
            CONSTRAINT DF_shop_products_created DEFAULT SYSUTCDATETIME(),
        updated_at datetime2 NOT NULL
            CONSTRAINT DF_shop_products_updated DEFAULT SYSUTCDATETIME(),
        CONSTRAINT CK_shop_products_price CHECK (price_pence >= 0),
        CONSTRAINT CK_shop_products_stock CHECK (stock_quantity >= 0),
        CONSTRAINT CK_shop_products_category CHECK (
            category IN (N'Books', N'Ceramics', N'Clocks', N'Furniture', N'Other', N'Watches')
        )
    );

    CREATE INDEX IX_shop_products_catalog
        ON dbo.shop_products(is_active, category, name);
END;

IF NOT EXISTS (SELECT 1 FROM dbo.shop_products WHERE name = N'Victorian Brass Pocket Watch')
BEGIN
    INSERT INTO dbo.shop_products (name, description, category, price_pence, stock_quantity)
    VALUES (N'Victorian Brass Pocket Watch', N'A sample antique pocket watch with a brass finish.', N'Watches', 4500, 5);
END;

IF NOT EXISTS (SELECT 1 FROM dbo.shop_products WHERE name = N'Oak Mantel Clock')
BEGIN
    INSERT INTO dbo.shop_products (name, description, category, price_pence, stock_quantity)
    VALUES (N'Oak Mantel Clock', N'A sample oak mantel clock for a classic home.', N'Clocks', 8250, 3);
END;

IF NOT EXISTS (SELECT 1 FROM dbo.shop_products WHERE name = N'Vintage Porcelain Tea Set')
BEGIN
    INSERT INTO dbo.shop_products (name, description, category, price_pence, stock_quantity)
    VALUES (N'Vintage Porcelain Tea Set', N'A sample porcelain tea set with a floral pattern.', N'Ceramics', 3200, 8);
END;

IF NOT EXISTS (SELECT 1 FROM dbo.shop_products WHERE name = N'Leather-Bound Book')
BEGIN
    INSERT INTO dbo.shop_products (name, description, category, price_pence, stock_quantity)
    VALUES (N'Leather-Bound Book', N'A sample decorative, leather-bound vintage book.', N'Books', 1875, 2);
END;
