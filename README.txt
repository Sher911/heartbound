HEARTBOUND WEBSITE - SETUP
1. Needs PHP 7.4+ with PDO SQLite (standard on most hosts). Upload the whole heartbound folder contents
   so that index.php sits in your website's root folder, with the assets folder next to it.
   To test locally: open a terminal inside this folder and run:  php -S localhost:8000
   then open http://localhost:8000
2. Edit config.php: contact details, member info, past performances, admin password.
3. Photos already included: assets/members/ (9 members).
   Still to add: assets/logo.png, assets/group.jpg
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
10. Past performances (About page) are managed in the admin dashboard under "Past performances": add a photo, title, date, venue and
   description. Photos upload to assets/gallery/ (must be writable by PHP).
11. Each past performance can also have a video: paste a YouTube/Vimeo link, or upload an MP4/WEBM (saved to assets/videos/, must be
   writable by PHP). Visitors tap "Watch video" on the About page to play it in a pop-up. Uploads can be up to 2,000 MB: the admin page
   sends the video in 4 MB pieces (with a progress bar), so the host's PHP upload limits don't matter. Keep the admin tab open until
   it says "Video uploaded". Change the limit with VIDEO_MAX_MB in db.php. Make sure your hosting has enough disk space.
