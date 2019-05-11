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
class Updator132 extends AbstractUpdator
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
        if (version_compare($version, '1.3.1', '<')) {
            $updator = new Updator131($this->handler);
            $result = $updator->upgrade($version);
            if (false === $result) {
                return $result;
            }
        }
        $result = $this->from132($version);

        return $result;
    }

    /**
     * Upgrade from previous version
     *
     * @param string $version
     *
     * @return bool
     */
    protected function from132($version)
    {
        
        $status = true;
        if (version_compare($version, '1.3.2', '<')) {
            $postModel = Pi::model('post', 'comment');
            $select = $postModel->select();
            $rowset = $postModel->selectWith($select);

            set_time_limit(0);

            foreach ($rowset as $row) {
                if ($row->writer != null) {
                    continue;
                } 
                
                $writer = 'USER';
                if (Pi::service('permission')->isAdmin('comment', $row->uid)) {
                    $writer = 'ADMIN'; 
                } else if ($row->module == 'guide') {
                    $rootModel = Pi::model('root', 'comment');
                    $select = $rootModel->select()->where('id = ' . $row->root);
                    $root  = $rootModel->selectWith($select)->current();
                    $item = Pi::api('item', 'guide')->getItem($root->item);
                    $owner = Pi::api('owner', 'guide')->getOwner($item['owner']);
                    if ($owner['uid'] == $row->uid) {
                        $writer = 'OWNER';    
                    }
                } 
                $row->writer = $writer;
                $row->save();
            }
            
        }

        return $status;
    }
}
