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
use Pi\Application\Installer\SqlSchema;


/**
 * System schema update handler
 *
 * @author Hossein Azizabadi <azizabadi@faragsoatesh.com>
 */
class Updator131 extends AbstractUpdator
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
        if (version_compare($version, '1.2.2', '<')) {
            $updator = new Updator122($this->handler);
            $result = $updator->upgrade($version);
            if (false === $result) {
                return $result;
            }
        }
        if (version_compare($version, '1.3.0', '<')) {
            $updator = new Updator130($this->handler);
            $result = $updator->upgrade($version);
            if (false === $result) {
                return $result;
            }
        }
        $result = $this->from131($version);

        return $result;
    }

    /**
     * Upgrade from previous version
     *
     * @param string $version
     *
     * @return bool
     */
    protected function from131($version)
    {
        
        $status = true;
        if (version_compare($version, '1.3.1', '<')) {
           // Alter table field `identity`
            $table = Pi::db()->prefix('post', 'comment');
            $sql =<<<'EOT'
ALTER TABLE %s ADD `writer` ENUM('ADMIN','OWNER','USER') NOT NULL AFTER `additional_images`;
EOT;
            $sql = sprintf($sql, $table);
            $status = $this->queryTable($sql);

        }

        return $status;
    }
}
