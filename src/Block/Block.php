<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 */

namespace Module\Comment\Block;

use Pi;

class Block
{
    /**
     * Recent comments block
     */
    public static function post($options = array(), $module = null)
    {
        // Set options
        $block = array();
        $block = array_merge($block, $options);
        // Set options
        $limit = intval($block['limit']);
        $where = array(
            'active' => 1
        );
        // Get posts list
        $posts = Pi::api('api', 'comment')->getList(
            \Module\Comment\Model\Post::TYPE_ALL,
            $where,
            $limit,
            null,
            null,
            isset($options['not_by_root']) ? $options['not_by_root'] : false,
            true
        );
        // Set render options
        $renderOptions = array(
            'user'      => array(
                'avatar'    => isset($options['avatar']) ? $options['avatar'] : 'medium',
                'attributes'    => array(
                    'alt'   => __('View profile'),
                ),
            ),
        );
        // Get render posts list
        $datas = Pi::api('api', 'comment')->renderList($posts, $renderOptions);
        $posts = array();
        foreach ($datas as $list) {
            foreach ($list as $key => $data) {
                $posts[$key] = $data;
            }
        }
         
        krsort($posts);        
        if (count($posts) > $limit) {
            while(count($posts) > $limit) {
                array_pop($posts);
            }
        } 
        $block['posts'] = $posts;
        foreach ($block['posts'] as &$post) {
            if ($post['type'] == 'REVIEW' && $post['reply'] == 0) { 
                $post['globalRating'] = Pi::api('api', 'comment')->globalRatingByPost($post['id']);
            }
            
        }
        
        // return
        return $block;
    }

    /**
     * Commented articles block
     */
    public static function article($options = array(), $module = null)
    {
        // Set options
        $block = array();
        $block = array_merge($block, $options);
        // Set options
        $limit = intval($block['limit']);
        // Top count
        $rowset = Pi::model('post', 'comment')->count(
            array('active' => 1),
            array('group' => 'root', 'limit' => $limit)
        );
        $roots = array();
        foreach ($rowset as $row) {
            $roots[$row['root']] = (int) $row['count'];
        }
        $rootIds = array_keys($roots);
        $targets = Pi::api('api', 'comment')->getTargetsByRoot($rootIds);
        array_walk($targets, function (&$target, $rootId) use ($roots) {
            $target['count'] = $roots[$rootId];
        });
        $block['targets'] = $targets;
        // return
        return $block;
    }

    /**
     * Top posters block
     */
    public static function user($options = array(), $module = null)
    {
        // Set options
        $block = array();
        $block = array_merge($block, $options);
        // Set options
        $limit = intval($block['limit']);
        // Top users
        $rowset = Pi::model('post', 'comment')->count(
            array('active' => 1),
            array('group' => 'uid', 'limit' => $limit)
        );
        $block['users'] = array();
        foreach ($rowset as $row) {
            $block['users'][$row['uid']] = array(
                'count' => (int) $row['count'],
            );
        }
        if ($block['users']) {
            $userNames = Pi::service('user')->mget(array_keys($block['users']), 'name');
            array_walk($block['users'], function (&$user, $uid) use ($userNames) {
                $user['name'] = $userNames[$uid];
                $user['profile'] = Pi::service('user')->getUrl('profile', $uid);
                $user['url'] = Pi::api('api', 'comment')->getUrl(
                    'user',
                    array('uid' => $uid)
                );
            });
        }
        // return
        return $block;
    }
}    

