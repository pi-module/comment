<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt BSD 3-Clause License
 */

namespace Module\Comment\Plugin;

use Pi;
use Module\User\Api\AbstractActivityCallback;

class Comment extends AbstractActivityCallback
{
    public function __construct()
    {
        
    }   
    
    public function get($uid, $limit, $page = 1, $name = '') 
    {
        $result = Pi::api('api', 'comment')->getComments($page, $uid, array('name' => $name));        
        return $result;
    }
    
    public function getCount($uid)
    {
        return Pi::api('api', 'comment')->getCount(array('uid' => $uid ? : Pi::user()->getId(), 'active' => 1, 'reply' => 0));
        
    }
    
}
