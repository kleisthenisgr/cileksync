# cileksync
Automatic xml feed product importer for Woocommerce

Reads from the xml feed and compares products with database.
If new products are found in the feed, it adds them to the DB.
If old products exist on the DB that have been removed from the XML feed, they will be removed from the feed.
If a change occurs in the products' attributes, script will check for inconsistences and bring DB up to date with XML feed.

Although the aforementioned functions take place automatically (as cron jobs), you can force them manually through the plugin's menu, 3 buttons - 1 for each function.
The manual syncing also offers visual logging information about the performed task.
