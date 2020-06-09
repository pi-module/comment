<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 */

namespace Module\Comment\Controller\Front;

use Pi;
use Pi\Mvc\Controller\ActionController;
use Module\Comment\Form\PostForm;
use Module\Comment\Form\PostFilter;

/**
 * Comment post controller
 *
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
class PostController extends ActionController
{
    /**
     * Comment post view
     *
     * @return string
     */
    public function indexAction()
    {
        $id      = _get('id', 'int') ?: 1;
        $post   = Pi::api('api', 'comment')->getPost($id);
        if ($post['reply'] > 0) {
            $post   = Pi::api('api', 'comment')->getPost($post['reply']);    
        }
        if ($post && $post['active']) {
            $where = array(
                'active' => 1,
                'reply' => 0,
                'root' => $post['root'],
                'type' => $post['type'] == 'REVIEW' ? 'REVIEW' : 'SIMPLE'
            );
        
            $select = Pi::model('post', 'comment')->select()->where($where)->order('id desc');
            $posts = Pi::model('post', 'comment')->selectWith($select);
            
            $count = 0;
            $perpage = $this->config('leading_limit') ?: 5;
            foreach ($posts as $apost) {
                if ($apost['id'] == $post['id']) {
                    break;
                }
                $count++;
                
            }
            $page = ((int)($count / $perpage)) + 1;
            $target = Pi::api('api', 'comment')->getTarget($post['root']);
            $type = $post['type'] == 'REVIEW' ? 'review' : 'comment'; 
            Pi::service('url')->redirect($target['url'] . '#' . $type . '/' . $page . '/' . $id, false, 301);
        } else {
            $this->view()->setTemplate('comment-404');
        }
    }

    /**
     * Edit a comment post
     */
    public function editAction()
    {
        Pi::service('authentication')->requireLogin();
        $currentUser    = Pi::service('user')->getUser();
        $currentUid     = $currentUser->get('id');
        $id             = _get('id', 'int') ?: 1;
        $redirect       = _get('redirect');

        $message    = '';
        $target     = array();
        $post = Pi::api('api', 'comment')->getPost($id);
        // Verify post
        if (!$post) {
            $message = __('Invalid post parameter.');
        // Verify author
        } elseif (!$currentUid
            || ($post['uid'] != $currentUid && !$currentUser->isAdmin('comment'))
        ) {
            $message = __('Operation denied.');
            $post = array();
        } else {
            $target = Pi::api('api', 'comment')->getTarget($post['root']);
            $user = array(
                'uid'       => $currentUid,
                'name'      => $currentUser->get('name'),
                'avatar'    => Pi::service('avatar')->get($currentUid),
            );
            $post['user'] = $user;
        }

        $title = __('Comment post edit');
        $this->view()->assign('comment', array(
            'title'     => $title,
            'post'      => $post,
            'target'    => $target,
            'message'   => $message,
        ));

        $data = array_merge($post, array(
            'redirect' => $redirect,
        ));
        $options = array(
            'markup'    => $post['markup'],
        );
        $form = Pi::api('api', 'comment')->getForm($data, $options);

        $this->view()->assign('form', $form);
        $this->view()->setTemplate('comment-edit');
    }

    /**
     * Reply a comment post
     */
    public function replyAction()
    {
        Pi::service('authentication')->requireLogin();
        $currentUser    = Pi::service('user')->getUser();
        $currentUid     = $currentUser->get('id');
        $id             = _get('id', 'int') ?: 1;
        $redirect       = _get('redirect');

        $message    = '';
        $target     = array();
        $post = Pi::api('api', 'comment')->getPost($id);
        // Verify post
        if (!$post) {
            $message = __('Invalid post parameter.');
        // Verify authentication
        } elseif (!$currentUid) {
            //$status = 0;
            $message = __('Operation denied.');
            $post = array();
        } else {
            $target = Pi::api('api', 'comment')->getTarget($post['root']);
            $post['content'] = Pi::api('api', 'comment')->renderPost($post);
            $user = array(
                'uid'       => $currentUid,
                'name'      => $currentUser->get('name'),
                'avatar'    => Pi::service('avatar')->get($currentUid),
            );
            $post['user'] = $user;
        }

        $title = __('Comment post reply');
        $this->view()->assign('comment', array(
            'title'     => $title,
            'post'      => $post,
            'target'    => $target,
            'message'   => $message,
        ));

        $data = array_merge($post, array(
            'redirect'  => $redirect,
            'root'      => $post['root'],
            'reply'     => $id,
            'id'        => '',
            'content'   => '',
        ));
        $form = Pi::api('api', 'comment')->getForm($data);

        $this->view()->assign('form', $form);
        $this->view()->setTemplate('comment-reply');
    }

    /**
     * Action for comment post submission
     */
    public function submitAction()
    {
        $this->view()->setTemplate(false);

        $result = $this->processPost();

        $redirect = '';
        if ($this->request->isPost()) {
            $return = (bool) $this->request->getPost('return');
            if (!$return) {
                $redirect = $this->request->getPost('redirect');
            }
        } else {
            $return = (bool) $this->params('return');
            if (!$return) {
                $redirect = $this->params('redirect');
            }
        }

        if (!$return) {
            $redirect = $redirect
                ? urldecode($redirect)
                : $this->getRequest()->getServer('HTTP_REFERER');
            if (!$redirect) {
                if (!empty($result['data'])) {
                    $redirect = Pi::api('api', 'comment')->getUrl('post', array(
                        'post' => $result['data']
                    ));
                } else {
                    $redirect = Pi::service('url')->assemble('comment');
                }
            }

            if ($result['data']) {
                if (strstr($redirect, '#')) {
                    $redirect = strstr($redirect, '#', true);
                }
                $this->redirect($redirect . '#comment-' . $result['data']);
            } else {
                $this->jump($redirect, $result['message']);
            }
        } else {
            return $result;
        }
    }

    /**
     * Process comment post submission
     *
     * @return array
     */
    protected function processPost()
    {
        $guestApprove   = Pi::service('config')->get('guest_approve', 'comment');
        $currentUser    = Pi::service('user')->getUser();
        $currentUid     = $currentUser->get('id');

        $id             = 0;
        $status         = 1;
        $isNew          = false;
        $isEnabled      = false;

        if (!$currentUid && $guestApprove === 0) {
            $status = -1;
            $message = __('Operation denied.');
        } elseif (!$this->request->isPost()) {
            $status = -2;
            $message = __('Invalid submission.');
        } else {
            $data = $this->request->getPost();

            // Temporarily force to text
            //$data['markup'] = 'text';

            $markup = $data['markup'];
            $ratings = isset($data['review']) && $data['review'] ? Pi::api('api', 'comment')->getRatings() : array();
            $form = new PostForm('comment-post', $markup, $ratings);
            $options = array('reply' => $data['reply'], 'review' => $data['review'], 'ratings' => $ratings);
            $form->setInputFilter(new PostFilter($options));
            $form->setData($data);
            if ($form->isValid()) {
                $values = $form->getData();
                if (!empty($values['root'])) {
                    $root = Pi::model('root', 'comment')->find($values['root']);
                    if (!$root) {
                        $status = -1;
                        $message = __('Root not found.');
                    } elseif (!$root['active']) {
                        $status = -1;
                        $message = __('Comment is disabled.');
                    }
                }
                if (!$currentUid && !$guestApprove) {
                    $status = -1;
                    $message = __('Guest information not set.');
                }
                if (0 < $status) {
                    // For new post
                    if (empty($values['id'])) {
                        if ($this->config('auto_approve')) {
                            $values['active'] = 1;
                            $isEnabled = true;
                        } else {
                            $values['active'] = 0;
                        }
                        $values['uid'] = $currentUid;
                        $values['ip'] = Pi::service('user')->getIp();
                        $isNew = true;
                    } else {
                        $post = Pi::api('api', 'comment')->getPost($values['id']);
                        $values['root'] = $post['root'];
                        $values['reply'] = $post['reply'];
                        $values['type'] = $post['type'];
                        $values['review'] = $post['type'] == 'REVIEW';
                        if (!$post) {
                            $status = -2;
                            $message = __('Invalid post parameter.');
                        } elseif ($currentUid != $post['uid']
                            && !$currentUser->isAdmin('comment')
                        ) {
                            $status = -1;
                            $message = __('Operation denied.');
                        } else {
                            $isEnabled = empty($post['active']) ? false : true;
                        }
                    }
                }
                if (0 < $status) {
                    $values['source'] = 'WEB';
                    $id = Pi::api('api', 'comment')->addPost($values, $currentUid);
                    if ($id) {
                        $status = 1;
                        $message = __('Comment post saved successfully.');
                    } else {
                        $status = 0;
                        $message = __('Comment post not saved.');
                    }
                }
            } else {
                $status = -1;
                $message = __('Invalid data, please check and re-submit.');
            }
        }

        if (0 < $status && $id) {
            if ($isNew) {
                if ($isEnabled) {
                    Pi::service('event')->trigger('post_publish', $id);
                } else {
                    Pi::service('event')->trigger('post_submit', $id);
                }
            } elseif ($isEnabled) {
                Pi::service('event')->trigger('post_update', $id);
            }
        }

        $result = array(
            'data'      => $id,
            'status'    => $status,
            'message'   => $message,
        );

        return $result;
    }

    /**
     * Approve/disapprove a post
     *
     * @return bool
     */
    public function approveAction()
    {
        $currentUser    = Pi::service('user')->getUser();
        $id         = _get('id', 'int');
        $flag       = _get('flag');
        $return     = _get('return');
        $redirect   = _get('redirect');

        if (!$currentUser->isAdmin('comment')) {
            $status     = -1;
            $message    = __('Operation denied.');
        } else {
            if (null === $flag) {
                $status = Pi::api('api', 'comment')->approve($id);
            } else {
                $status = Pi::api('api', 'comment')->approve($id, $flag);
            }
            $message = $status
                ? __('Operation succeeded.') : __('Operation failed');
        }

        if (0 < $status && $id) {
            if (null === $flag || $flag) {
                Pi::service('event')->trigger('post_enable', $id);
            } else {
                Pi::service('event')->trigger('post_disable', $id);
            }
        }

        if (!$return) {
            $redirect = $redirect
                ? urldecode($redirect)
                : $this->getRequest()->getServer('HTTP_REFERER');
            if (!$redirect) {
                $redirect = Pi::api('api', 'comment')->getUrl('post', array(
                    'post' => $id
                ));
            }
            $this->jump($redirect, $message, $status == 1 ? 'success' : 'error');
        } else {
            $result = array(
                'status'    => (int) $status,
                'message'   => $message,
                'data'      => $id,
            );

            return $result;
        }
    }

    /**
     * Delete a comment post
     *
     * @return array
     */
    public function deleteAction()
    {
        Pi::service('authentication')->requireLogin();
        $currentUser    = Pi::service('user')->getUser();
        $currentUid     = $currentUser->get('id');

        $id             = _get('id', 'int');
        $return         = _get('return');
        $redirect       = _get('redirect');

        $post           = Pi::api('api', 'comment')->getPost($id);
        
        $time = $post['time_updated'] ? $post['time_updated'] : $post['time'];
        $canDelete = false;
        if (time() - $time <= Pi::service('config')->get('time_to_edit_or_delete', 'comment')) {
            $canDelete = true;    
        }
        
        if (!$post) {
            $status = 422;
            $message = __('Invalid parameters.');
        } elseif (!$currentUid) {
            $status = 403;
            $message = __('Forbidden.');
        } elseif ($currentUid != $post['uid']
            && !$currentUser->isAdmin('comment')
        ) {
            $status = 403;
            $message = __('Forbidden.');
        } elseif (!$canDelete && !$currentUser->isAdmin('comment')) {
            $status = 403;
            $message = __('Forbidden.');
        } else {
            $status         = Pi::api('api', 'comment')->deletePost($id);
            $message        = $status
                ? __('Operation succeeded.') : __('Operation failed');
        }

        if (0 < $status && $id) {
            Pi::service('event')->trigger('post_delete', $post['root']);
        }
        // find next comment 
        $nextComment == null;
        if ($post['reply']) {
            $select = Pi::model('post', 'comment')->select()->where(array('active' => 1, 'reply' => $post['reply'], new \Laminas\Db\Sql\Predicate\Expression('id < ' . $post['id'])))->order('id DESC');
            $row = Pi::model('post', 'comment')->selectWith($select)->current();
            if ($row == null) {
                $nextComment = $post['reply'];
            }   else {
                $nextComment = $row['id'];
            }
        } else {
           $select = Pi::model('post', 'comment')->select()->where(array('active' => 1, 'reply' => 0, 'type' => $post['type'], new \Laminas\Db\Sql\Predicate\Expression('id > ' . $post['id'])))->order('id');
           $row = Pi::model('post', 'comment')->selectWith($select)->current();
           if ($row != null) {
                $nextComment = $row['id'];
            }
        }
        
        if (!$return) {
            if ($nextComment == null) {
                $redirect = $redirect
                    ? urldecode($redirect)
                    : $this->getRequest()->getServer('HTTP_REFERER');
                if (!$redirect) {
                    $redirect = Pi::service('url')->assemble('comment');
                }
                if (strstr($redirect, '#')) {
                    $redirect = strstr($redirect, '#', true);
                }
                if ($post['type'] == 'REVIEW') {
                    $redirect = $redirect . '#write-review';
                } else {
                    $redirect = $redirect . '#js-comment-form';
                }
            } else {
                $redirect = Pi::api('api', 'comment')->getUrl('post', array('post' => $nextComment));
                
            }
            $this->jump($redirect, $message, $status > 0 ? 'success' : 'error');
        } else {
            $this->response->setStatusCode($status);
            return array(
                'message' => $message
            );
        }
    }

    /**
     * Get privileged operation list on a post
     *
     * @return array
     */
    public function operationAction()
    {
        $id     = _get('id', 'int');
        $uid    = _get('uid', 'int');

        $status = 1;
        $message = '';
        $operations = array();

        $postRow = null;
        if (!$uid && $id) {
            $postRow = Pi::model('post', 'comment')->find($id);
            if ($postRow) {
                $uid = (int) $postRow['uid'];
            }
        }
        if (!$id || !$uid) {
            $status = -1;
            $message = __('Invalid parameters.');
        } else {
            $currentUser    = Pi::service('user')->getUser();
            $currentUid     = $currentUser->get('id');
            $ops = array(
                'login' => array(
                    'title' => __('Login'),
                    'url'   => Pi::service('authentication')->getUrl('login'),
                ),
                'edit' => array(
                    'title' => __('Edit'),
                    'url'   => Pi::api('api', 'comment')->getUrl(
                        'edit',
                        array('post' => $id)
                    ),
                ),
                'delete' => array(
                    'title' => __('Delete'),
                    'url'   => Pi::api('api', 'comment')->getUrl(
                        'delete',
                        array('post' => $id)
                    ),
                ),
                'reply' => array(
                    'title' => __('Reply'),
                    'url'   => Pi::api('api', 'comment')->getUrl(
                        'reply',
                        array('post' => $id)
                    ),
                ),
                'approve' => array(
                    'title' => __('Enable/Disable'),
                    'url'   => Pi::api('api', 'comment')->getUrl(
                        'approve',
                        array(
                            'post'  => $id,
                            'flag'  => !(int) $postRow['active'],
                        )
                    ),
                ),
            );

            if (!$currentUid) {
                $operations = $ops['login'];
            } elseif ($currentUser->isAdmin('comment')) {
                $operations = $ops;
                unset($operations['login']);
            } elseif ($uid == $currentUid) {
                $operations = $ops;
                unset($operations['login'], $operations['approve']);
            } elseif ($uid != $currentUid) {
                $operations = $ops['reply'];
            }
        }
        $result = array(
            'status'    => $status,
            'message'   => $message,
            'data'      => $operations,
        );

        return $result;
    }
}
