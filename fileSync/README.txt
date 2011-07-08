This project implements SyncML specifications.
Main goal is to provide server for sync purposes to various devices and even other syncs like http://www.memotoo.com/.

You should use this settings, to make a sync:
URL: http://your.host/path-to-sync/index.php
username and password - any you want, user will be created password will be stored. should not contain any special characters!
Locations for sync items should be filled.
For example:
Contacts location: contacts
Tasks location: tasks
etc..

For every user a directory is created in this dir (see base_dir in config file) based on provided username (user_dir). Under user dir there is a file named profile (used mainly to store password) and other dirs (for contacts, tasks, etc - see above). DirName_SrcDevID.state is for storing sync anchors and md5 hashes of items (at the state they are on device). If, for some reasons, you need to do a slow sync (full resync), then just change anchor lines in sessions file or delete it at all.

In this dir (user_dir) there is a sessions file - it stores session ID's for authentication support purposes.

If unrestriced parameter in config/config.xml is set to value, greater than 0, then new users will be created automatically. Otherwise - new users will be unauthenticated. I hope...

After sync you can look at the log.txt for details of exchange. in.xml will contain latest received request and out.xml will contain latest response. progress.xml is containing all the exchange ever made (cleaned manually).

Server is in development state. Logging and some debug output is turned on. There are 2 switches in config/config.xml to turn it off (do_log and keep_exchange). do_log is responsible for log.txt and keep_exchange - for in.xml, out.xml and progress.xml files.

Global configuration file is config/config.xml. It seems to be well documented.

Project supports xml-compression. Compression is used when sender's side uses it too - otherwise plain xml.

For now project still in beta. It supports syncs, but there is no server-to client data yet. It not supports transactions (i.e. if exchange fails - modifications should be discarded, but they don't).

** DO NOT USE this server for critical data **
We have no responsibility for any data loss.
** This software provided as-is and you can use it for your own responsibility **

* * * *
SERVER not contains any protection against hackers. It might lead to unwanted access to server data or data corruption. BE AWARE!
* * * *
The only one protection is config/.htaccess

For now, server supports Sync. In spite of sync support sync is still buggy and needs more testing and debugging. Use with care.

There are options for conflicts in config file: server, client, duplicate, delete, merge. You can use shorter versions: ser, cli, dup, del, mer respectively. Actually only 3 first letter matters: you can use "MeRrY Christmas" instead of "merge".
If conflicting item received - client will be notified and action from config.xml is applied. Actions are:
* server: server wins. If client sends conflicting item - item reported as conflicting and then server replaces client's version. Both client and server will contain server's version (code 419).
* client: client wins. If client sends conflicting item - item reported as conflicting and then server overwrites it's version. Both client and server will contain client's version (code 208).
* duplicate: item duplicated. If client sends conflicting item - item reported as conflicting and then server will rename it's own element and will send it back (code 209).
* delete: both items deleted. If client sends conflicting item - item reported as conflicting and then server will delete it's own version and then asks client to delete it's item. USE WITH CAUTION! YOU LOOSE ANY CONFLICTING ITEMS. (code 409 - just "conflict").
* merge: changes merged. If client sends conflicting item - item reported as conflicting and then server will TRY to merge changes of both items and send it back. (code 207)
Note about merge: Items compared line by line. If at least one line changed by both sides - merging fails and server do action, mentioned in config.xml as action2. Server behavior unpredicted if action2 = "merge", but it will not try to merge again in any way.
Merging is not implemented for now.

Please be careful when editing config.xml - any tag mismatch will cause server to silently go mad (logging will be forced, see log file).

If after sending item to server and receiving it's back it's changed - then it's a device bug. Long fields inside vcf file are somewhat hyphenated. Anyway - just delete "=\r\n" in such fields and it should become normal. Often happens on chars from others codepages. Maybe someday there will be a fix/workaround for this.

If you do sync of multiple elements (cantacts & tasks) then there can be no server responce. IT's a bug. I'll fix it later. Sync one-by-one. Bug happens especially when there are a lot of items to sync.

Vcf items contains quoted-printable text. On Linux you can view it with `perl -p -e 's/=([a-fA-F0-9]{2})/chr(hex($1))/eg' filename1 filename2 ...` . 

Any way - server should work fine for day-to-day backup usage or for transferring elements to server. In most cases server elements are simple .vcf files without extension ('.vcf').

I'm thinking about MySQL and total code rewrite.
Things, that are currently not supported:
* merging
* searching item by FN/content (not by id) when adding
* transactions (still don't know when to end successfully)
* meta-data (content-type of items)
* chnuked send big portions of data
* web-interface
* correct sync of multiple items (see bug above)

If you want to send log.txt to me for debug - please also attach progress.xml - it'll be very helpful.

Please, feel free to contact us (thru the site) or by mail nyxisn@users.sourceforge.net at any time
