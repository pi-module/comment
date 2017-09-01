<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt BSD 3-Clause License
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
class Updator130 extends AbstractUpdator
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
        $result = $this->from130($version);

        return $result;
    }

    /**
     * Upgrade from previous version
     *
     * @param string $version
     *
     * @return bool
     */
    protected function from130($version)
    {
        
        $status = true;
        if (version_compare($version, '1.3.0', '<')) {
           // Alter table field `identity`
            $table = Pi::db()->prefix('post', 'comment');
            $sql =<<<'EOT'
ALTER TABLE %s ADD `type` ENUM(  "SIMPLE",  "REVIEW" ) NOT NULL DEFAULT  'SIMPLE',
ADD  `time_experience` INT( 11 ) NULL DEFAULT NULL,
ADD  `main_image` VARCHAR(255),
ADD  `additional_images` TEXT;
EOT;
            $sql = sprintf($sql, $table);
            $status = $this->queryTable($sql);

            if (false === $status) {
                return $status;
            }

            $sql = <<<'EOD'
CREATE TABLE `{rating_type}` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(32)  NULL,
  KEY `author` (`id`)
);

CREATE TABLE `{post_rating}` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post` varchar(32)  NULL,
  `rating_type` varchar(32)  NULL,
  `rating` tinyint(1)  NOT NULL,
  KEY `author` (`id`)
);

CREATE TABLE `{subscription}` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `root`  int(10) unsigned NOT NULL,
  KEY `author` (`id`)
);

INSERT INTO `{post_rating}` (`id`, `type`) VALUES (NULL, 'Global');
EOD;

            SqlSchema::setType("comment");
            $sqlHandler = new SqlSchema;
            try {
                $status = $sqlHandler->queryContent($sql);
            } catch (\Exception $exception) {
                
                return $status;
            }
        }

        return $status;
    }
}
