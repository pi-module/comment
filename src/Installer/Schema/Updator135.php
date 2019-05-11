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
class Updator135 extends AbstractUpdator
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
        if (version_compare($version, '1.3.3', '<')) {
            $updator = new Updator133($this->handler);
            $result = $updator->upgrade($version);
            if (false === $result) {
                return $result;
            }
        }
        $result = $this->from135($version);

        return $result;
    }

    /**
     * Upgrade from previous version
     *
     * @param string $version
     *
     * @return bool
     */
    protected function from135($version)
    {
        $status = true;
        if (version_compare($version, '1.3.5', '<')) {
            ini_set('max_execution_time', 0);
            $select = Pi::model('timeline_log', 'user')->select()->where('module = "comment"')->order('id');
            $rowset = Pi::model('timeline_log', 'user')->selectWith($select);
            $ids = array();            
            foreach ($rowset as $row) { 
                $link = $row['link'];
                $link = explode('/', $link);
                $id = $link[count($link)-1];
                $post   = Pi::api('api', 'comment')->getPost($id);
                if (!$post) {
                    $ids[] = $id;
                    $row->delete();
                    continue;
                }
                if (!$post['active']) {
                    $ids[] = $id;
                    $row->delete();
                    continue;
                }
                if (in_array($id, $ids)) {
                    $ids[] = $id;
                    $row->delete();
                    continue;
                }
                
                if ($post['uid'] == $row->uid) {
                    $target = Pi::api('api', 'comment')->getTarget($post['root']);
                    if ($target['url'] == '') {
                        $ids[] = $id;
                        $row->delete();
                        continue;
                    } else {
                        $row->data = json_encode(array('comment' => (int)$post['id']));
                        $ids[] = $id;
                        $row->save();
                        continue;
                        
                    }
                } else {
                    $ids[] = $id;
                    $row->delete();
                    continue;
                }
                $ids[] = $id;
            }
        }

        return $status;
    }
}
