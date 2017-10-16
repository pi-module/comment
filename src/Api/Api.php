<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt BSD 3-Clause License
 */

namespace Module\Comment\Api;

use Pi;
use Pi\Application\Api\AbstractApi;
use Pi\Db\Sql\Where;
use Pi\Db\RowGateway\RowGateway;
use Module\Comment\Form\PostForm;
use Zend\Mvc\Router\RouteMatch;

/**
 * Comment manipulation APIs
 *
 * - load($routeMatch)
 * - add($root, array $data)
 * - addRoot(array $data)
 * - get($id)
 * - getForm(array $data)
 * - getUrl($type, array $options)
 * - getRoot(array $condition|$id)
 * - getTarget($root)
 * - getList(array $condition|$root, $limit, $offset, $order)
 * - getTargetList(array $condition, $limit, $offset, $order)
 * - getCount(array $condition|$root)
 * - update($id, array $data)
 * - delete($id)
 * - approve($id, $flag)
 * - enable($root, $flag)
 * - deleteRoot($root, $flag)
 *
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
class Api extends AbstractApi
{
    /** @var string Module name */
    protected $module = 'comment';

    /** @var string[] Post table columns */
    protected $postColumn = array(
        'id',
        'root',
        'reply',
        'uid',
        'identity',
        'email',
        'ip',
        'time',
        'time_updated',
        'content',
        'markup',
        'active',
        'module',
        'review', 
        'time_experience',
        'main_image',
        'additional_images',
        'source'
        
    );

    /** @var string[] Comment root table columns */
    protected $rootColumn = array(
        'id',
        'module',
        'type',
        'item',
        'author',
        'active'
    );

    /**
     * Canonize comment post data
     *
     * @param $data
     *
     * @return array
     */
    protected function canonizePost($data)
    {
        $result = array();

        if (array_key_exists('active', $data)) {
            if (null === $data['active']) {
                unset($data['active']);
            } else {
                $data['active'] = (int) $data['active'];
            }
        }

        foreach ($data as $key => $value) {
            if (in_array($key, $this->postColumn)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
    protected function canonizeRating($data)
    {
        $result = array();
        foreach ($data as $key => $value) {
            if (strstr($key, 'rating-')) {
                $result[$key] = $value;
            }
        }

        return $result;
    
    }

    /**
     * Canonize comment root data
     *
     * @param $data
     *
     * @return array
     */
    protected function canonizeRoot($data)
    {
        $result = array();

        if (array_key_exists('active', $data)) {
            if (null === $data['active']) {
                unset($data['active']);
            } else {
                $data['active'] = (int) $data['active'];
            }
        }

        foreach ($data as $key => $value) {
            if (in_array($key, $this->rootColumn)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Get URLs
     *
     * For AJAX request, set `$options['return'] = 1;`
     *
     * @param string    $type
     * @param array     $options
     *
     * @return string
     */
    public function getUrl($type, array $options = array())
    {
        $params = array();
        switch ($type) {
            case 'post':
                if (!empty($options['post'])) {
                    $params = array(
                        'controller'    => 'post',
                        'id'            => (int) $options['post'],
                    );
                    unset($options['post']);
                }
                break;
            case 'approve':
                if (!empty($options['post'])) {
                    $params = array(
                        'controller'    => 'post',
                        'action'        => $type,
                        'id'            => (int) $options['post'],
                    );
                    if (isset($options['flag'])) {
                        $params['flag'] = $options['flag'];
                        unset($options['flag']);
                    }
                    unset($options['post']);
                }
                break;
            case 'delete':
            case 'edit':
            case 'reply':
                if (!empty($options['post'])) {
                    $params = array(
                        'controller'    => 'post',
                        'action'        => $type,
                        'id'            => (int) $options['post'],
                    );
                    unset($options['post']);
                }
                break;
            case 'submit':
                $params = array('controller' => 'post', 'action' => $type);
                break;
            case 'list':
                $params = array('controller' => 'list');
                break;
            case 'root':
                if (!empty($options['root'])) {
                    $rootId = $options['root'];
                    unset($options['root']);
                } elseif ($root = Pi::api('api', 'comment')->getRoot($options)) {
                    $rootId = $root['id'];
                } else {
                    $rootId = 0;
                }
                if ($rootId) {
                    $params = array(
                        'controller'    => 'list',
                        'root'          => (int) $rootId,
                    );
                }
                break;
            case 'user':
                $params = array(
                    'controller'    => 'list',
                    'action'        => 'user',
                );
                if (!empty($options['uid'])) {
                    $params['uid'] = (int) $options['uid'];
                    unset($options['uid']);
                }
                break;
            case 'module':
                if (!empty($options['name'])) {
                    $params = array(
                        'controller'    => 'list',
                        'action'        => 'module',
                        'name'          => $options['name'],
                    );
                    if (!empty($options['type'])) {
                        $params['type'] = $options['type'];
                        unset($options['type']);
                    }
                    unset($options['name']);
                }
                break;
            default:
                break;
        }
        if ($options) {
            $params = array_merge($options, $params);
        }
        /*
        // For AJAX calls
        if (isset($options['return'])) {
            $params['return'] = $options['return'];
        }
        */
        $url = Pi::url(Pi::service('url')->assemble('comment', $params));

        return $url;
    }

    public function findRoot($params)
    {
        // Look up root against route data
        $root           = array();
        $module         = $params['module'];
        $controller     = $params['controller'];
        $action         = $params['action'];
        $typeList       = Pi::registry('type', 'comment')->read($module, '', null);
        if (isset($typeList['route'][$controller][$action])) {
            $lookupList = $typeList['route'][$controller][$action];
        } elseif (isset($typeList['locator'])) {
            $lookupList = $typeList['locator'];
        } else {
            return false;
        }        
        $active = true;
        $lookup = function ($data) use ($params, &$active) {
            // Look up via locator callback
            if (!empty($data['locator'])) {
                $locator    = new $data['locator']($params['module']);
                $item       = $locator->locate($params);
                $active     = $data['active'];

                return $item;
            }

            // Look up via route
            if (!isset($params[$data['identifier']])) {
                return false;
            }
            $item = $params[$data['identifier']];
            if ($data['params']) {
                foreach ($data['params'] as $param => $value) {
                    if (!isset($params[$param]) || $value != $params[$param]) {
                        return false;
                    }
                }
            }
            $active = $data['active'];

            return $item;
        };

        // Look up against controller-action
        foreach ($lookupList as $key => $data) {
            $item = $lookup($data, $active);
            if ($item) {
                $root = array(
                    'module'    => $module,
                    'type'      => $key,
                    'item'      => $item,
                );
                break;
            }
        }
        
        return array(
            'root' => $root,
            'active' => $active);
    }
    /**
     * Load comment data for rendering against matched route
     *
     * Data array:
     * - root: [id, ]module, type, item, active
     * - count
     * - posts: id, uid, ip, content, time, active
     * - users: uid, name, avatar, url
     * - url_list
     * - url_submit
     * - url_ajax
     *
     * @param RouteMatch|array|string $routeMatch
     * @param array $options
     *
     * @return array|bool
     */
    public function load($routeMatch, array $options = array())
    {
        $review = $options['review'];
        if ($routeMatch instanceof RouteMatch) {
            $params = $routeMatch->getParams();
        } else {
            $params = (array) $routeMatch;
        }
        $limit = Pi::config('leading_limit', 'comment') ?: 5;
        $offset = isset($options['page']) && $options['page'] >= 1 ? ($options['page'] - 1) * $limit : 0; 

        // Look up root against route data
        $data = $this->findRoot($params);
        $root = $data['root'];
        $active = $data['active'];

        if (!$root) {
            return false;
        }

        // Load translations
        Pi::service('i18n')->load('module/comment:default');

        $rootData = $this->getRoot($root);
        if (!$active) {
            $root['active'] = 0;
            $rootData['active'] = 0;
        }
        // Check against cache
        $result = null;
        if (isset($rootData['id'])) { 
            $result = Pi::service('comment')->loadCache($rootData['id'] . '-' . ($review ? \Module\Comment\Model\Post::TYPE_REVIEW : \Module\Comment\Model\Post::TYPE_COMMENT)  . '-' .  $limit . $offset);
            if ($result) {
                if (Pi::service()->hasService('log')) {
                    Pi::service('log')->info(
                        sprintf('Comment root %d is cached.', $rootData['id'])
                    );
                }
                if (!$active) {
                    $result['root']['active'] = 0;
                }
               
            }
        }
        
        if (!$result) {
            $result = array(
                'root'          => $rootData ?: $root,
                'count'         => 0,
                'posts'         => array(),
                'users'         => array(),
                'url_list'      => '',
                'url_submit'    => $this->getUrl('submit'),
            );
    
            if ($rootData) {
                $result['count'] = $this->getCount($rootData['id'], $review ? \Module\Comment\Model\Post::TYPE_REVIEW : \Module\Comment\Model\Post::TYPE_COMMENT);
                
                //vd($result['count']);
                if ($result['count']) {
                    $posts = $this->getList(
                        $review ? \Module\Comment\Model\Post::TYPE_REVIEW : \Module\Comment\Model\Post::TYPE_COMMENT,
                        $rootData['id'], 
                        $limit, 
                        $offset
                    );
                    
                    $opOption = isset($options['display_operation'])
                        ? $options['display_operation']
                        : Pi::service('config')->module('display_operation', 'comment');
                    $avatarOption = isset($options['avatar'])
                        ? $options['avatar']
                        : 'normal';
                    $renderOptions = array(
                        'target'    => false,
                        'operation' => $opOption,
                        'user'      => array(
                            'avatar'    => $avatarOption,
                        ),
                    );
                    $posts = $this->renderList($posts, $renderOptions);
                    $result['posts'] = $posts;
                    $result['url_list'] = $this->getUrl(
                        'root',
                        array('root'  => $rootData['id'])
                    );
    
                    $status = Pi::service('comment')->saveCache(
                        $rootData['id'] . '-' . $review . '-' . $limit . $offset,
                        $result
                    );
                    if ($status && Pi::service()->hasService('log')) {
                        Pi::service('log')->info(sprintf(
                            'Comment root %d is saved to cache.',
                            $rootData['id']
                        ));
                    }
                }
                 
            }
        }
        
        // Subscription
        $subscribe = 0;
        if (Pi::user()->getId() > 0 && $rootData) {
            $select = Pi::model('subscription', 'comment')->select()->where(array('root' => $rootData['id'], 'uid' => Pi::user()->getId()));
            $subscribe = Pi::model('subscription', 'comment')->selectWith($select)->count();
        }
        $result['subscribe'] = $subscribe;
        
        // Rating 
        $globalRatings = array();
        if ($review && isset($rootData['id'])) {
            $globalRatings = $this->globalRatings($rootData['id']);
        }
        
        $result['globalRatings'] = $globalRatings;
        
        return $result;
    }

    /**
     * Render post content
     *
     * @param array|RowGateway|string $post
     *
     * @return string
     */
    public function renderPost($post)
    {
        $content = '';
        $markup = 'text';
        if ($post instanceof RowGateway || is_array($post)) {
            $content = $post['content'];
            $markup = $post['markup'];
        } elseif (is_string($post)) {
            $content = $post;
        }
        //$renderer = ('markdown' == $markup || 'html' == $markup) ? 'html' : 'text';
        //$parser = ('markdown' == $markup) ? 'markdown' : false;
        //$result = Pi::service('markup')->render($content, $renderer, $parser);

        $result = Pi::api('markup', 'comment')->render($content, $markup);

        return $result;
    }

    /**
     * Render list of posts
     *
     * Options:
     *  - user:
     *      - field: 'name'
     *      - avatar: false|<size>
     *      - url: 'profile'|'comment'
     *
     *  - operation: with array
     *      - uid: int
     *      - user: object
     *      - section: 'front'|'admin'
     *      - list: array(<name> => <title>)
     *      - level: member, author, admin; default as author
     *  - operation: with string for level
     *
     *  - target
     *
     * - Comprehensive mode
     * ```
     *  $posts = Pi::api('api', 'comment')->renderList($posts, array(
     *      'user'      => array(
     *          'field'         => 'name',
     *          'url'           => 'comment',
     *          'avatar'        => 'small',
     *          'attributes'    => array(
     *              'alt'   => __('View profile'),
     *          ),
     *      ),
     *      'target'    => true,
     *      'operation'     => array(
     *          'uid'       => Pi::service('user')->getId(),
     *          'section'   => 'admin',
     *          'level'     => 'author',
     *      ),
     *  ));
     * ```
     *
     * - Lean mode
     * ```
     *  $posts = Pi::api('api', 'comment')->renderList($posts, array(
     *      'user'      => true,
     *      'target'    => true,
     *      'operation' => true,
     *  ));
     * ```
     *
     * - Default mode
     * ```
     *  $posts = Pi::api('api', 'comment')->renderList($posts);
     * ```
     *
     * @param array $posts
     * @param array $options
     *
     * @return array
     */
    public function renderList(array $postsData, array $options = array())
    {
        if (!$postsData) {
            return $postsData;
        }
        
        foreach ($postsData as &$posts) {

        
            $ops = array();
            // Build authors
            if (!isset($options['user']) || false !== $options['user']) {
                $op = isset($options['user'])
                    ? (array) $options['user']
                    : array();
                $label  = !empty($op['field']) ? $op['field'] : 'name';
                $avatar = isset($op['avatar']) ? $op['avatar'] : 'small';
                $url    = isset($op['url']) ? $op['url'] : 'profile';
                $attrs  = isset($op['attributes']) ? $op['attributes'] : array();
    
                $uids = array();
                foreach ($posts as $post) {
                    $uids[] = $post['uid'];
                }
                if ($uids) {
                    $uids = array_unique($uids);
                    $users = Pi::service('user')->mget($uids, array($label, 'role', 'location_city', 'location_country'));
                    $avatars = null;
                    if (false !== $avatar) {
                        $avatars = Pi::service('avatar')->getList(
                            $uids,
                            $avatar,
                            $attrs
                        );
                    }
                    array_walk(
                        $users,
                        function (&$data, $uid) use ($url, $avatars) {
                            if ('comment' == $url) {
                                $data['url'] = $this->getUrl(
                                    'user',
                                    array('uid' => $uid)
                                );
                            } else {
                                $data['url'] = Pi::url(Pi::service('user')->getUrl(
                                    'profile',
                                    $uid
                                ));
                            }
                            if (null !== $avatars) {
                                $data['avatar'] = $avatars[$uid];
                            }
                            $data['contributions'] = $this->getContributions($uid);
                        }
                    );
                }
                $users[0] = array(
                    'avatar'    => Pi::service('avatar')->get(0, $avatar),
                    'url'       => Pi::url('www'),
                    'name'      => __('Guest'),
                );
    
                $ops['users'] = $users;
            }
    
            // Build operations
            if (!isset($options['operation']) || $options['operation']) {
                if (!isset($options['operation'])) {
                    $op = array();
                } elseif (is_string($options['operation'])) {
                    $op = array('level' => $options['operation']);
                } else {
                    $op = (array) $options['operation'];
                }
    
                $uid = $user = $list = $section = $admin = null;
                if (isset($op['uid'])) {
                    $uid = (int) $op['uid'];
                }
                if (isset($op['user'])) {
                    $user = $op['user'];
                }
                if (null === $uid) {
                    if (null === $user) {
                        $uid = Pi::service('user')->getId();
                    } else {
                        $uid = $user->get('id');
                    }
                }
    
                if (isset($op['section'])) {
                    $section = $op['section'];
                } else {
                    $section = Pi::engine()->section();
                }
    
                if (isset($op['list'])) {
                    $list = (array) $op['list'];
                }
                if (null === $list) {
                    $list = array(
                        'edit'      => __('Edit'),
                        'approve'   => __('Enable'),
                        'delete'    => __('Delete'),
                        'reply'     => __('Reply'),
                    );
                }
    
                $level      = isset($op['level']) ? $op['level'] : 'author';
                $isAdmin    = Pi::service('permission')->isAdmin('comment', $uid);
                $setOperations = function ($post) use (
                    $list,
                    $uid,
                    $isAdmin,
                    $level,
                    $section
                ) {
                    if ('admin' == $level && $isAdmin) {
                        $opList = array('edit', 'approve', 'delete', 'reply');
                    } elseif ('author' == $level && $uid == $post['uid']) {
                        $opList = array('edit', 'delete', 'reply');
                    } elseif ($uid) {
                        $opList = array('reply');
                    } else {
                        $opList = array();
                    }
                    $operations = array();
                    foreach ($opList as $op) {
                        if (!isset($list[$op])) {
                            continue;
                        }
                        $title = $url = '';
                        switch ($op) {
                            case 'edit':
                            case 'delete':
                                if ('admin' == $section) {
                                    $url = Pi::url(Pi::service('url')->assemble(
                                        'admin',
                                        array(
                                            'module'        => 'comment',
                                            'controller'    => 'post',
                                            'action'        => $op,
                                            'id'            => $post['id'],
                                        )
                                    ));
                                } else {
                                    $url = $this->getUrl($op, array(
                                        'post' => $post['id']
                                    ));
                                }
                                $title = $list[$op];
                                break;
                            case 'approve':
                                if ($post['active']) {
                                    $flag = 0;
                                    $title = __('Disable');
                                } else {
                                    $flag = 1;
                                    $title = __('Enable');
                                }
                                if ('admin' == $section) {
                                    $url = Pi::url(Pi::service('url')->assemble(
                                        'admin',
                                        array(
                                            'module'        => 'comment',
                                            'controller'    => 'post',
                                            'action'        => $op,
                                            'id'            => $post['id'],
                                            'flag'          => $flag,
                                        )
                                    ));
                                } else {
                                    $url = $this->getUrl($op, array(
                                        'post'  => $post['id'],
                                        'flag'  => $flag,
                                    ));
                                }
                                break;
                            case 'reply':
                                if ('admin' == $section) {
                                } else {
                                    $url = $this->getUrl($op, array(
                                        'post' => $post['id']
                                    ));
                                }
                                $title = $list[$op];
                                break;
                            default:
                                break;
                        }
                        if (!$url || !$title) {
                            continue;
                        }
    
                        $operations[$op] = array(
                            'title' => $title,
                            'url'   => $url,
                        );
                    }
    
                    return $operations;
                };
    
                $ops['operations'] = array(
                    'uid'       => $uid,
                    'is_admin'  => $isAdmin,
                    'section'   => $section,
                    'list'      => $list,
                    'callback'  => $setOperations,
                );
            }
    
            // Build targets
            if (!isset($options['target']) || $options['target']) {
                $targets = array();
                $rootIds = array();
                foreach ($posts as $post) {
                    $rootIds[] = (int) $post['root'];
                }
                if ($rootIds) {
                    $rootIds = array_unique($rootIds);
                    $targets = $this->getTargetList(array(
                        'root'  => $rootIds
                    ));
                }
                $ops['targets'] = $targets;
            }
    
            array_walk($posts, function (&$post) use ($ops) {
                $post['content'] = $this->renderPost($post);
                $post['url'] = $this->getUrl('post', array(
                    'post'  => $post['id']
                ));
                if (!empty($ops['users'])) {
                    $uid = (int) $post['uid'];
                    if (isset($ops['users'][$uid])) {
                        $post['user'] = $ops['users'][$uid];
                    } else {
                        $post['user'] = $ops['users'][0];
                    }
                }
                if (!empty($ops['targets'])) {
                    $root = (int) $post['root'];
                    if (isset($ops['targets'][$root])) {
                        $post['target'] = $ops['targets'][$root];
                    } else {
                        $post['target'] = $ops['targets'][0];
                    }
                }
                if (!empty($ops['operations'])
                    && is_callable($ops['operations']['callback'])
                ) {
                    $post['operations'] = $ops['operations']['callback']($post);
                }
            });
        }

        return $postsData;
    }

    /**
     * Get comment post edit form
     *
     * @param array $data
     *
     * @return PostForm
     */
    public function getForm(array $data = array(), array $options = array())
    {
        $name = isset($options['name']) ? $options['name'] : '';
        $markup = isset($options['markup'])
            ? $options['markup']
            : Pi::config('markup_format', $this->module);
        $ratings = isset($options['review']) && $options['review']
            ? $this->getRatings()
            : array();
        $form = new PostForm($name, $markup, $ratings);
        if ($data) {
            $form->setData($data);
        }

        return $form;
    }
    
    public function getRatings()
    {
        $select = Pi::model('rating_type', 'comment')->select();
        $rowset = Pi::model('rating_type', 'comment')->selectWith($select);

        $ratings = array();        
        foreach ($rowset as $row) {
            $ratings[$row['id']] = $row->type;
        }
        
        return $ratings;
    }

    /**
     * Add comment of an item
     *
     * @param array $data root, uid, content, module, item, type, time
     *
     * @return int|bool
     */
    public function addPost(array $data, $uid)
    {
        $id = isset($data['id']) ? (int) $data['id'] : 0;
        if (isset($data['id'])) {
            unset($data['id']);
        }
        $postData = $this->canonizePost($data);
        $postData['type'] = $postData['review'] == 0  ? 'SIMPLE' : 'REVIEW';
        if (isset($postData['time_experience'])) {
            $postData['time_experience'] = strtotime($postData['time_experience']);
            if ($postData['time_experience'] > strtotime('TODAY')) {
                return false;
            }
        }
        unset($postData['review']);
        if (!$id) {
            // Add root if not exist
            if (empty($postData['root'])) {
                $rootId = $this->addRoot($data);
                if (!$rootId) {
                    return false;
                }
                $postData['root'] = $rootId;
                $root = Pi::model('root', 'comment')->find($postData['root']);
                
            // Verify root
            } else {
                $root = Pi::model('root', 'comment')->find($postData['root']);
                if (!$root) {
                    return false;
                }
                $type = Pi::registry('type', 'comment')->read(
                    $root['module'],
                    $root['type']
                );
                // Exit if type is disabled or not exist
                if (!$type) {
                    return false;
                }
                if (empty($postData['module'])) {
                    $postData['module'] = $root['module'];
                }
            }
                
            if (!isset($postData['time'])) {
                $postData['time'] = time();
            }
            if (isset($postData['time_updated'])) {
                unset($postData['time_updated']);
            }
            
            $postData['writer'] = 'USER';
            if (Pi::service('permission')->isAdmin('comment', $uid)) {
                $postData['writer'] = 'ADMIN'; 
            } else if ($postData['module'] == 'guide') {
                $item = Pi::api('item', 'guide')->getItem($root['item']);
                $owner = Pi::api('owner', 'guide')->getOwner($item['owner']);
                if ($owner['uid'] == $uid) {
                    $postData['writer'] = 'OWNER';    
                }
            } 
            $row = Pi::model('post', 'comment')->createRow($postData);
        } else {
            $row = Pi::model('post', 'comment')->find($id);
            $time = $row->time_updated ? $row->time_updated : $row->time;
            
            $canEdit = false;
            if (time() - $time <= Pi::service('config')->get('time_to_edit_or_delete', 'comment') || Pi::service('user')->getUser()->isAdmin('comment')) {
                $canEdit = true;    
            }
            
            $uid = Pi::service('user')->getUser()->get('id');
            if ($uid == 0 ||  ($uid != $row->uid && !Pi::service('user')->getUser()->isAdmin('comment')) || !$canEdit) {
                return false;
            } 
                
            if (!isset($postData['time_updated'])) {
                $postData['time_updated'] = time();
            }
            foreach (array('module', 'reply', 'root', 'time', 'uid', 'reply', 'source') as $key) {
                if (isset($postData[$key])) {
                    unset($postData[$key]);
                }
            }
            $row->assign($postData);
            $root = $postData['root'];
            
        }

        try {
            $row->save();
            $newId = (int) $row->id;
            $root = $row->root;
            
        } catch (\Exception $d) {
            $newId = false;
        }
        
        $ratingData = $this->canonizeRating($data);
        Pi::model('post_rating', 'comment')->delete(array('post' => $newId));
        foreach ($ratingData as $key => $value) {
            if (strstr($key, 'rating-')) {
                $ratingType = str_replace('rating-', '', $key);
                $rowRating = Pi::model('post_rating', 'comment')->createRow(
                    array(
                        'post' => $newId,
                        'rating_type' => $ratingType,
                        'rating' => $value
                    )
                );  
                $rowRating->save();
            }   
        }
        
        // notify, except for edit 
        if (!$id) {
            $this->notify($root, $uid);
            if ($row->module == 'guide' && $row->type =='REVIEW') {
                $this->notifyOwner($root, $uid);
            }
        }
        if ($newId) {
            $this->subscription($root, $data['subscribe']);   
        }

        return $newId;
    }
    
    public function subscription($root, $subscription)
    {
        $rootData = Pi::api('api', 'comment')->getRoot($root);
        $rowData = array(
                'uid' => Pi::user()->getId(),
                'root' => $rootData['id']                 
        );
        Pi::model('subscription', 'comment')->delete($rowData);
        
        if ($subscription) {
            $row = Pi::model('subscription', 'comment')->createRow();
            $row->assign($rowData);
            $row->save();
        }
        
    }
    private function notifyOwner($root, $exclude)
    {
        // Canonize item 
        $select = Pi::model('root', 'comment')->select()->where(array('id' => $root));
        $row = Pi::model('root', 'comment')->selectWith($select)->current();
        
        $information  = Pi::api ('comment', $row['module'])->canonize($row['item']);
        
        // Find uid
        $item = Pi::api('item', 'guide')->getItem($row->item);
        $owner = Pi::api('owner', 'guide')->getOwner($item['owner']);
        $uid = $owner['uid'];
       
        $user = Pi::user()->getUser($uid);
        if ($user == null || $uid == $exclude) {
            return;
        }
        
        // Message Notification
        $to = array(
           $user['email'] => $user['name'],
        );

        // Send mail and notif
        Pi::service('notification')->send(
            $to,
            'notify_owner_review.txt',
            $information,
            'comment',
            $uid
        );
        
    }
    private function notify($root, $exclude)
    {
        // get Root 
        $select = Pi::model('root', 'comment')->select()->where(array('id' => $root));
        $row = Pi::model('root', 'comment')->selectWith($select)->current();
        $row = $row->toArray();  

        // canonize item
        $type = Pi::registry('type', 'comment')->read(
            $row['module'],
            $row['type']
        );
        $callback = $type['callback'];
        $handler = new $callback($row['module']);
        $information = $handler->canonize($row['item']);
            
        // Find uid
        $select = Pi::model('subscription', 'comment')->select()->where(array('root' => $root));
        $rowset = Pi::model('subscription', 'comment')->selectWith($select);
        $uids = array();
        foreach ($rowset as $row) {
             if ($row['uid'] == $exclude) {
                 continue;
             }
            $uids[] = $row['uid'];
        }
        
        // Add notification
        $data = Pi::service('mail')->template(
            array(
                'file'      => 'notify_comment.txt',
                'module'    => 'comment',
            ),
            $information
        );
        foreach ($uids as $uid) {
            $user = Pi::user()->getUser($uid);
            if ($user == null) {
                continue;
            }
            // Message Notification
            $to = array(
               $user['email'] => $user['name'],
            );

            // Send mail and notif
            Pi::service('notification')->send(
                $to,
                'notify_comment.txt',
                $information,
                'comment',
                $uid
            );
        }
    }

    /**
     * Add comment root of an item
     *
     * @param array $data module, item, author, type, time
     *
     * @return int|bool
     */
    public function addRoot(array $data)
    {
        
        $id = isset($data['id']) ? (int) $data['id'] : 0;
        if (isset($data['id'])) {
            unset($data['id']);
        }
        $type = Pi::registry('type', 'comment')->read(
            $data['module'],
            $data['type']
        );
        // Exit if type is disabled or not exist
        if (!$type) {
            return false;
        }

        if (!isset($data['author'])) {
            $callback = $type['callback'];
            $handler = new $callback($data['module']);
            $source = $handler->get($data['item']);
            $data['author'] = $source['uid'];
        }
        $rootData = $this->canonizeRoot($data);
        if (!$id) {
            $row = Pi::model('root', 'comment')->createRow($rootData);
        } else {
            $row = Pi::model('root', 'comment')->find($id);
            $row->assign($rootData);
        }

        try {
            $row->save();
            $id = (int) $row->id;
        } catch (\Exception $d) {
            $id = false;
        }

        return $id;
    }

    /**
     * Get a comment
     *
     * @param int $id
     *
     * @return array|bool   uid, content, time, active, IP
     */
    public function getPost($id)
    {
        $row = Pi::model('post', 'comment')->find($id);
        $result = $row ? $row->toArray() : false;

        return $result;
    }

    /**
     * Get root
     *
     * @param int|array $condition
     *
     * @return array    Module, type, item, callback, active
     */
    public function getRoot($condition)
    {
        if (is_scalar($condition)) {
            $row = Pi::model('root', 'comment')->find($condition);
            $result = $row ? $row->toArray() : array();
        } else {
            $where = $this->canonizeRoot($condition);
            $rowset = Pi::model('root', 'comment')->select($where);
            if (count($rowset) == 1) {
                $result = $rowset->current()->toArray();
            } else {
                $result = array();
            }
        }

        return $result;
    }

    /**
     * Get target(s) content
     *
     * @param int|int[] $item
     * @param array $options Callback or module + type
     *
     * @return mixed
     */
    public function getTargetContent($item, array $options)
    {
        if (empty($options['module'])) {
            return array();
        }

        $items = (array) $item;
        if (!empty($options['callback'])) {
            $handler = new $options['callback']($options['module']);
            $list = array_values($handler->get($items));
        } else {
            $vars = array('title', 'url', 'uid', 'time');
            $conditions = array(
                'module'    => $options['module'],
                'type'      => empty($options['type']) ? '' : $options['type'],
                'id'        => $items,
            );
            $list = Pi::service('module')->content($vars, $conditions);
        }
        if (is_scalar($item)) {
            $result = current($list);
        } else {
            $result = $list;
        }

        return $result;
    }

    /**
     * Get target content of a root
     *
     * @param int $root
     *
     * @return array|bool    Title, url, uid, time
     */
    public function getTarget($root)
    {
        $rootData = $this->getRoot($root);
        if (!$rootData) {
            return false;
        }
        $target = Pi::model('type', 'comment')->select(array(
            'module'    => $rootData['module'],
            'name'      => $rootData['type'],
        ))->current();
        if (!$target) {
            return false;
        }
        $data = array(
            'module'    => $rootData['module'],
            'type'      => $rootData['type'],
            'callback'  => $target['callback'],
        );
        $result = $this->getTargetContent($rootData['item'], $data);

        return $result;
    }

    /**
     * Get target list by root IDs
     *
     * @param array $ids
     *
     * @return array
     */
    public function getTargetsByRoot(array $ids)
    {
        $result = array();
        if (!$ids) {
            return $result;
        }

        $rowset = Pi::model('root', 'comment')->select(array('id' => $ids));
        //$roots = array();
        $items = array();
        foreach ($rowset as $row) {
            $id = (int) $row['id'];
            //$roots[$id] = $row->toArray();
            $items[$row['module']][$row['type']][$row['item']] = $id;
        }
        //d($items);
        $types = Pi::registry('type', 'comment')->read();
        $list = array();
        foreach ($items as $module => $mList) {
            foreach ($mList as $type => $cList) {
                if (!isset($types[$module][$type])) {
                    continue;
                }
                /*
                $callback = $types[$module][$type]['callback'];
                $handler = new $callback($module);
                $targets = $handler->get(array_keys($cList));
                foreach ($targets as $item => $target) {
                    $root = $cList[$item];
                    $list[$root] = $target;
                }
                */
                $data = array(
                    'module'    => $module,
                    'type'      => $type,
                    'callback'  => $types[$module][$type]['callback'],
                );
                $targets = $this->getTargetContent(array_keys($cList), $data);
                foreach ($targets as $target) {
                    $root = $cList[$target['id']];
                    $list[$root] = $target;
                }
            }
        }
        foreach ($ids as $root) {
            $result[$root] = $list[$root];
        }

        return $result;
    }

    /**
     * Get multiple targets being commented
     *
     * @param array|Where $condition
     * @param int|null    $limit
     * @param int         $offset
     * @param string|null $order
     *
     * @return array List of targets indexed by root id
     */
    public function getTargetList(
        $condition,
        $limit          = null,
        $offset         = 0,
        $order          = null
    ) {
        $result = array();

        if ($condition instanceof Where) {
            $where = $condition;
        } else {
            $whereRoot = array();
            $wherePost = $this->canonizePost($condition);
            /**/
            if (isset($wherePost['active'])) {
                $whereRoot['active'] = $wherePost['active'];
            }
            /**/
            if (isset($condition['type'])) {
                $whereRoot['type'] = $condition['type'];
            }

            $where = array();
            foreach ($wherePost as $field => $value) {
                $where['post.' . $field] = $value;
            }
            foreach ($whereRoot as $field => $value) {
                $where['root.' . $field] = $value;
            }
        }

        $select = Pi::db()->select();
        $select->from(
            array('root' => Pi::model('root', 'comment')->getTable()),
            array('id', 'module', 'type', 'item', 'author')
        );

        $select->join(
            array('post' => Pi::model('post', 'comment')->getTable()),
            'post.root=root.id',
            //array()
            array('time', 'uid')
        );
        $select->group('post.root');
        $select->where($where);
        $limit = (null === $limit)
            ? Pi::config('list_limit', 'comment')
            : (int) $limit;
        $order = (null === $order) ? 'post.time desc' : $order;
        if ($limit) {
            $select->limit($limit);
        }
        if ($offset) {
            $select->offset($offset);
        }
        if ($order) {
            $select->order($order);
        }

        $types      = Pi::registry('type', 'comment')->read();
        $items      = array();
        $keyList    = array();
        $rowset     = Pi::db()->query($select);
        foreach ($rowset as $row) {
            $root = (int) $row['id'];
            $keyList[] = $root;
            $items[$row['module']][$row['type']][$row['item']] = array(
                'root'          => $root,
                'comment_time'  => (int) $row['time'],
                'comment_uid'   => (int) $row['uid'],
            );
        }

        $targetList = array();
        foreach ($items as $module => $mList) {
            foreach ($mList as $type => $cList) {
                if (!isset($types[$module][$type])) {
                    continue;
                }
                /*
                $callback = $targets[$module][$type]['callback'];
                $handler = new $callback($module);
                $targets = $handler->get(array_keys($cList));
                foreach ($targets as $item => $target) {
                    $root = $cList[$item]['root'];
                    $targetList[$root] = array_merge($target, $cList[$item]);
                }
                */

                $data = array(
                    'module'    => $module,
                    'type'      => $type,
                    'callback'  => $types[$module][$type]['callback'],
                );
                $targets = $this->getTargetContent(array_keys($cList), $data);
                foreach ($targets as $target) {
                    $item = $target['id'];
                    $root = $cList[$item]['root'];

                    if(is_array($cList[$item])){
                        $targetList[$root] = array_merge($target, $cList[$item]);
                    }
                }
            }
        }
        foreach ($keyList as $key) {
            $result[$key] = &$targetList[$key];
        }

        return $result;
    }

    /**
     * Get multiple comments
     *
     * @param int|array|Where   $condition Root id or conditions
     * @param int               $limit
     * @param int               $offset
     * @param string            $order
     *
     * @return array|bool
     */
    public function getList($type,  $condition, $limit = null, $offset = 0, $order = null, $notByRoot = false)
    {
        $result = array();
        $specialCondition = false;
        if ($condition instanceof Where) {
            $where  = $condition;
            $specialCondition = true;
        } else {
            $whereRoot = array();
            if (is_array($condition)) {
                $wherePost = $this->canonizePost($condition);
                if (isset($condition['type'])) {
                    $whereRoot['type'] = $condition['type'];
                }
                if (isset($condition['author'])) {
                    $whereRoot['author'] = $condition['author'];
                }
                if ($whereRoot) {
                    $specialCondition = true;
                }
            } else {
                $wherePost = array(
                    'root'      => (int) $condition,
                    'active'    => 1,
                );
            }
            //vd($wherePost);
            if ($specialCondition) {
                $where = array();
                foreach ($wherePost as $field => $value) {
                    $where['post.' . $field] = $value;
                }
                foreach ($whereRoot as $field => $value) {
                    $where['root.' . $field] = $value;
                }
            } else {
                $where = $wherePost;
            }
        }

        $postRatingTable = Pi::model('post_rating', 'comment')->getTable();
        $ratingTypeTable = Pi::model('rating_type', 'comment')->getTable();
        $postTable = Pi::model('post', 'comment')->getTable();
        $order = null === $order ? 'time desc' : $order;
        
        $whereType = null;
        switch ($type) {
            case \Module\Comment\Model\Post::TYPE_REVIEW :
                $whereType = "REVIEW";
                break; 
            case \Module\Comment\Model\Post::TYPE_COMMENT :
                $whereType = "SIMPLE";
                break;
        } 
        
        $select = Pi::db()->select();
        $select->from(array('post' => $postTable))
        ->group('post.id');

        if ($whereType != null) {
            $select->where('post.type = "' . $whereType . '"');
        }
                
        if ($specialCondition) {
            $order = null === $order ? 'post.time desc' : $order;
            $select->join(
                array('root' => Pi::model('root', 'comment')->getTable()),
                'root.id=post.root',
                array()
            );
        }

        $select->where($where);
        $limit = (null === $limit)
            ? Pi::config('list_limit', 'comment')
            : (int) $limit;
        if ($limit) {
            $select->limit($limit);
        }
        if ($order) {
            $select->order($order);
        }
        if ($offset) {
            $select->offset($offset);
        }
        $rowset = Pi::db()->query($select);
        $ids = array();
        foreach ($rowset as $row) {
            $post = (array) $row;
           
            $post['rating'] = array();
            if ($notByRoot) {
                $result[$post['id']][$post['id']] = $post;
            } else {
                if (!isset($result[$post['reply']])) {
                    $result[$post['reply']] = array();
                }
                $result[$post['reply']][$post['id']] = $post;
            }
            $ids[] = $post['id'];
            
        }
        
        if (count($ids) == 0) {
            return $result;
        }
        // Find rating for posts
        $select = Pi::db()->select();
        $select->from(array('post' => $postTable))->columns(array('id', 'reply'))
        ->join(
            array('post_rating' => $postRatingTable),
            'post_rating.post = post.id',
            array('rating'),
            'left'
        )
        ->join(
            array('rating_type' => $ratingTypeTable),
            'rating_type.id = post_rating.rating_type',
            array('rating_type_id' => 'id', 'type'),
            'left'
        )->group('post_rating.id')
        ->where(new \Zend\Db\Sql\Predicate\In(addslashes('post.id'), $ids));
        $rowset = Pi::db()->query($select);
        foreach ($rowset as $row) {
            $post = (array) $row;
             if ($notByRoot) {
                $result[$post['id']][$post['id']]['rating'][$post['rating_type_id']] = array('type' => $post['type'], 'rating' =>  $post['rating']); 
             } else {
                $result[$post['reply']][$post['id']]['rating'][$post['rating_type_id']] = array('type' => $post['type'], 'rating' =>  $post['rating']);
             }
        }
        
        return $result;
    }

    /**
     * Get comment count
     *
     * @param int|array     $condition Root id or conditions
     *
     * @return int|bool
     */
    public function getCount($condition = array(), $type = \Module\Comment\Model\Post::TYPE_ALL)
    {
        $isJoin = false;
        if ($condition instanceof Where) {
            $where = $condition;
            $isJoin = true;
        } else {
            $whereRoot = array();
            //$wherePost = array();
            if (is_array($condition)) {
                $wherePost = $this->canonizePost($condition);
                if (isset($condition['type'])) {
                    $whereRoot['type'] = $condition['type'];
                }
                if (isset($condition['author'])) {
                    $whereRoot['author'] = $condition['author'];
                }
                if ($whereRoot) {
                    $isJoin = true;
                }
            } else {
                $wherePost = array(
                    'root'      => (int) $condition,
                    'active'    => 1,
                );
            }
            if ($isJoin) {
                $where = array();
                foreach ($wherePost as $field => $value) {
                    $where['post.' . $field] = $value;
                }
                foreach ($whereRoot as $field => $value) {
                    $where['root.' . $field] = $value;
                }
            } else {
                $where = $wherePost;
            }
        }

        $whereType = null;
        switch ($type) {
            case \Module\Comment\Model\Post::TYPE_REVIEW :
                $whereType = "REVIEW";
                break; 
            case \Module\Comment\Model\Post::TYPE_COMMENT :
                $whereType = "SIMPLE";
                break;
        } 
        
        if ($whereType != null) {
            $where['type'] = $whereType;
        }
        

        if (!$isJoin) {
            $count = Pi::model('post', 'comment')->count($where);
        } else {
            $select = Pi::db()->select();
            $select->from(
                array('post' => Pi::model('post', 'comment')->getTable())
            );
            $select->columns(array('count' => Pi::db()->expression('COUNT(*)')));
            $select->join(
                array('root' => Pi::model('root', 'comment')->getTable()),
                'root.id=post.root',
                array()
            );
            $select->where($where);
            $row = Pi::db()->query($select)->current();
            $count = (int) $row['count'];
        }

        return $count;
    }

    /**
     * Get target count
     *
     * @param array|Where $condition
     *
     * @return int
     */
    public function getTargetCount($condition = array())
    {
        if ($condition instanceof Where) {
            $where = $condition;
        } else {
            $whereRoot = array();
            $wherePost = $this->canonizePost($condition);
            if (isset($wherePost['active'])) {
                $whereRoot['active'] = $wherePost['active'];
            }
            if (isset($condition['type'])) {
                $whereRoot['type'] = $condition['type'];
            }

            $where = array();
            foreach ($wherePost as $field => $value) {
                $where['post.' . $field] = $value;
            }
            foreach ($whereRoot as $field => $value) {
                $where['root.' . $field] = $value;
            }
        }

        $select = Pi::db()->select();
        $select->from(
            array('root' => Pi::model('root', 'comment')->getTable())
        );
        $select->columns(array(
            'count' => Pi::db()->expression('COUNT(DISTINCT root.id)')
        ));
        $select->join(
            array('post' => Pi::model('post', 'comment')->getTable()),
            'post.root=root.id',
            array()
        );
        //$select->group('post.root');
        $select->where($where);
        $row = Pi::db()->query($select)->current();
        $count = (int) $row['count'];

        return $count;
    }

    /**
     * Delete a comment
     *
     * @param int   $id
     *
     * @return bool
     */
    public function deletePost($id)
    {
        $row = Pi::model('post', 'comment')->find($id);
        if (!$row) {
            return false;
        }
        $result = true;
        try {
            $row->delete();
        } catch (\Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Approve/Disapprove a comment
     *
     * @param int  $id
     * @param bool $flag
     *
     * @return bool
     */
    public function approve($id, $flag = true)
    {
        $row = Pi::model('post', 'comment')->find($id);
        if (!$row) {
            return false;
        }
        if ((int) $row->active == (int) $flag) {
            return false;
        }
        $row->active = (int) $flag;
        $result = true;
        try {
            $row->save();
        } catch (\Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Enable/Disable comments for a target
     *
     * @param array|int $root
     * @param bool      $flag
     *
     * @return bool
     */
    public function enable($root, $flag = true)
    {
        $model = Pi::model('root', 'comment');
        if (is_int($root)) {
            $row = $model->find($root);
            if (!$row) {
                return false;
            }
        } else {
            $root = $this->canonizeRoot($root);
            $row = $model->select($root)->current();
            if (!$row) {
                $row = $model->createRow($root);
            }
        }
        if ($row->id && (int) $row->active == (int) $flag) {
            return false;
        }
        $row->active = (int) $flag;
        $result = true;
        try {
            $row->save();
        } catch (\Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Delete comment root and its comments
     *
     * @param int  $root
     *
     * @return bool
     */
    public function delete($root)
    {
        $row = Pi::model('root', 'comment')->find($root);
        if (!$row) {
            return false;
        }
        $result = true;
        Pi::model('post', 'comment')->delete(array('root' => $root));
        try {
            $row->delete();
        } catch (\Exception $e) {
            $result = false;
        }

        return $result;
    }
    
    public function globalRatings ($root)
    {
        $result = array();
        if (!$root) {
            $select = Pi::model('rating_type', 'comment')->select();
            $rowset = Pi::model('rating_type', 'comment')->selectWith($select);
    
            $ratings = array();        
            foreach ($rowset as $row) {
                $result[$row['id']] = array(
                    'type' => $row['type'],
                    'rating' => 0,
                );
            }
            $result['0'] = array(
                'type' => 'resume',
                'rating' => 0,
                'number' => 0  
            );
            return $result;
            
        }
        
        $postRatingTable = Pi::model('post_rating', 'comment')->getTable();
        $ratingTypeTable = Pi::model('rating_type', 'comment')->getTable();
        $postTable = Pi::model('post', 'comment')->getTable();
        
        $select = Pi::db()->select();
        $select->from(array('post' => $postTable))->columns(array('id'))
        ->join(
            array('post_rating' => $postRatingTable),
            'post_rating.post = post.id',
            array('avgrating' => Pi::db()->expression('AVG(rating)'))
        )
        ->join(
            array('rating_type' => $ratingTypeTable),
            'rating_type.id = post_rating.rating_type',
            array('rating_type_id' => 'id', 'type')
        )->group('post.root')
        ->where(array('post.root = ' . $root));
        
        $rowset = Pi::db()->query($select);
        foreach ($rowset as $row) {
            $result['0'] = array(
                'type' => 'resume',
                'rating' => round($row['avgrating']),
                'number' => round($row['avgrating'], 1)  
            );
        }
        
       $select = Pi::db()->select();
       $select->from(array('post' => $postTable))->columns(array('id'))
        ->join(
            array('post_rating' => $postRatingTable),
            'post_rating.post = post.id',
            array('avgrating' => Pi::db()->expression('AVG(rating)'))
        )
        ->join(
            array('rating_type' => $ratingTypeTable),
            'rating_type.id = post_rating.rating_type',
            array('rating_type_id' => 'id', 'type')
        )->group('rating_type.id')
        ->where(array('post.root = ' . $root));
        
        $rowset = Pi::db()->query($select);
        foreach ($rowset as $row) {
            $result[$row['rating_type_id']] = array(
                'type' => $row['type'],
                'rating' => round($row['avgrating']),
            );
        }
        
        return $result;
    }

    public function globalRatingByPost ($post)
    {
        
        $postRatingTable = Pi::model('post_rating', 'comment')->getTable();
        $ratingTypeTable = Pi::model('rating_type', 'comment')->getTable();
        $postTable = Pi::model('post', 'comment')->getTable();
        
        $select = Pi::db()->select();
        $select->from(array('post' => $postTable))->columns(array('id'))
        ->join(
            array('post_rating' => $postRatingTable),
            'post_rating.post = post.id',
            array('avgrating' => Pi::db()->expression('AVG(rating)'))
        )
        ->group('post.id')
        ->where(array('post.id = ' . $post));
        
        $row = Pi::db()->query($select)->current();
        return round($row['avgrating']);
    }
    
    public function getContributions ($uid) {
        $where = array(
            'uid' => $uid,
            'reply' => 0 
        );
        return Pi::model('post', 'comment')->count($where);        
    }
}
