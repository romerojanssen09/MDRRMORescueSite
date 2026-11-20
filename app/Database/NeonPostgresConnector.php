<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;

class NeonPostgresConnector extends PostgresConnector
{
    /**
     * Append Neon endpoint option for libpq clients without SNI support.
     */
    protected function addSslOptions($dsn, array $config)
    {
        $dsn = parent::addSslOptions($dsn, $config);

        $host = $config['host'] ?? '';
        if (is_string($host) && preg_match('/^(ep-[a-z0-9-]+?)(?:-pooler)?\..+\.neon\.tech$/i', $host, $matches)) {
            if (! str_contains($dsn, 'options=')) {
                $dsn .= ";options=endpoint={$matches[1]}";
            }
        }

        return $dsn;
    }
}
