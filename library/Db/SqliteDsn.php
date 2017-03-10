<?php

namespace Fasim\Library\Db;

use Fasim\Library\DbDsn;

/**
 * SqliteDsn
 *
 */
class SqliteDsn extends DbDsn
{

/**
 * The database in a sqlite dsn is a path, not a database name
 *
 * @var bool
 */
    protected $databaseIsPath = true;
}
