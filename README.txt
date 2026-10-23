HEARTBOUND WEBSITE - SETUP
1. Needs PHP 7.4+ with PDO SQLite (standard on most hosts). Upload the whole heartbound folder contents
   so that index.php sits in your website's root folder, with the assets folder next to it.
   To test locally: open a terminal inside this folder and run:  php -S localhost:8000
   then open http://localhost:8000
2. Edit config.php: contact details, member info, past performances, admin password.
3. Photos already included: assets/members/ (9 members).
   Still to add: assets/logo.jpg, assets/group.jpg, assets/gallery/p1.jpg..p4.jpg
4. Admin page: yourdomain/admin.php (confirm/decline requests, block dates).
5. Make sure the folder is writable by PHP so data.sqlite can be created automatically.

ADMIN (added)
6. Click "Admin login" in the thin bar at the very top of any page and enter the password set in config.php
   ('admin_password'). CHANGE THE DEFAULT PASSWORD before going live.
7. In the admin dashboard you can: confirm / decline / reset booking requests, block dates, and add, edit
   or delete members (with optional photo upload to assets/members/).
8. Members are copied from config.php into the database the first time the site runs. After that, edit them
   from the admin dashboard (changes to the members list in config.php are no longer used).
9. The folders assets/members and assets/gallery must be writable by PHP for photo uploads.
