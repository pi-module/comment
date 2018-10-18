<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 */

namespace   Module\Comment\Installer\Schema;

use Pi;
use Pi\Application\Installer\Schema\AbstractUpdator;

/**
 * System schema update handler
 *
 * @author Hossein Azizabadi <azizabadi@faragsoatesh.com>
 */
class Updator122 extends AbstractUpdator
{
    /**
     * Update module table schema
     *
     * @param string $version
     *
     * @return bool
     */
    public function upgrade($version)
    {
        if (version_compare($version, '1.1.0', '<')) {
            $updator = new Updator110($this->handler);
            $result = $updator->upgrade($version);
            if (false === $result) {
                return $result;
            }
        }
        $result = $this->from122($version);

        return $result;
    }

    /**
     * Upgrade from previous version
     *
     * @param string $version
     *
     * @return bool
     */
    protected function from122($version)
    {
        $status = true;
        if (version_compare($version, '1.2.2', '<')) {
            // Alter table field `identity`
            $table = Pi::db()->prefix('post', 'comment');
            $sql =<<<'EOT'
ALTER TABLE %s ADD `identity` VARCHAR(64) NOT NULL DEFAULT '' AFTER `uid`;
EOT;
            $sql = sprintf($sql, $table);
            $status = $this->queryTable($sql);

            if (false === $status) {
                return $status;
            }

            // Alter table field `email`
            $table = Pi::db()->prefix('post', 'comment');
            $sql =<<<'EOT'
ALTER TABLE %s ADD `email` VARCHAR(64) NOT NULL DEFAULT '' AFTER `identity`;
EOT;
            $sql = sprintf($sql, $table);
            $status = $this->queryTable($sql);

            if (false === $status) {
                return $status;
            }
        }

        return $status;
    }
}
