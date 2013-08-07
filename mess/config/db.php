<?php
/**
 * User: Gobasu
 * Date: 07.08.13
 * Time: 16:46
 */
CONST host = 'localhost';
CONST username = 'root';
CONST password = 'zaq1@WSXcde3';
CONST database = 'mess';

use alchemy\storage\Storage;
use alchemy\storage\sql\MySQL;


Storage::add(new MySQL(host, username, password, database));
