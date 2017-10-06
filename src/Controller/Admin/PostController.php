<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt BSD 3-Clause License
 */

namespace Module\Comment\Controller\Admin;

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
     * Comment post
     *
     * @return string
     */
    public function indexAction()
    {
        $id = _get('id', 'int') ?: 1;
        $post = Pi::api('api', 'comment')->getPost($id);
        $review = $post['type'] == 'REVIEW';
        $ratings = array();
        $ratingsType = array();
        if ($review) {
            
            $select = Pi::model('rating_type', 'comment')->select();
            $rowset = Pi::model('rating_type', 'comment')->selectWith($select);
            foreach ($rowset as $row) {
                $ratingsType[$row['id']] = $row['type']; 
            }
            $select = Pi::model('post_rating', 'comment')->select()->where(array('post' => $post['id']));
            $rowset = Pi::model('post_rating', 'comment')->selectWith($select);
            foreach ($rowset as $row) {
                $ratings[] = $row->toArray(); 
            }
        }
        
        $target = array();
        if ($post) {
            $post['content'] = Pi::api('api', 'comment')->renderPost($post);
            $target = Pi::api('api', 'comment')->getTarget($post['root']);
            $user = Pi::service('user')->get($post['uid'], array('name'));
            $user['url'] =  Pi::service('user')->getUrl('profile', $post['uid']);
            $user['avatar'] = Pi::service('avatar')->get($post['uid']);
            $post['user'] = $user;
            $active = $post['active'];
            $post['operations'] = array(
                'edit'  => array(
                    'title' => _a('Edit'),
                    'url'   => $this->url('', array(
                        'action'        => 'edit',
                        'id'            => $id,
                    )),
                ),
                'delete'  => array(
                    'title' => _a('Delete'),
                    'url'   => $this->url('', array(
                        'action'        => 'delete',
                        'id'            => $id,
                    )),
                ),
                'approve'  => array(
                    'title' => $active ? _a('Disable') : _a('Enable'),
                    'url'   => $this->url('', array(
                        'action'        => 'approve',
                        'id'            => $id,
                        'flag'          => $active ? 0 : 1,
                    )),
                ),
            );
        }
        $title = _a('Comment post');
        $this->view()->assign('comment', array(
            'title'     => $title,
            'post'      => $post,
            'target'    => $target,
            'review' => $review,
            'ratings' => $ratings,
            'ratings_type' => $ratingsType,
             
        ));
        $this->view()->setTemplate('comment-view');
    }

    public function editAction()
    {
        $id = _get('id', 'int') ?: 1;
        $redirect = _get('redirect');

        $post = Pi::api('api', 'comment')->getPost($id);
        $target = array();
        if ($post) {
            $target = Pi::api('api', 'comment')->getTarget($post['root']);
            $user = Pi::service('user')->get($post['uid'], array('name'));
            $user['url'] =  Pi::service('user')->getUrl('profile', $post['uid']);
            $user['avatar'] = Pi::service('avatar')->get($post['uid']);
            $post['user'] = $user;
        }

        $title = _a('Comment post edit');
        $this->view()->assign('comment', array(
            'title'     => $title,
            'post'      => $post,
            'target'    => $target,
        ));
        
        $review = $post['type'] == 'REVIEW';
        
        $data = array_merge($post, array(
            'redirect' => $redirect,
        
        ));
        
        $ratings = array();
        if ($review) {
            $data['time_experience'] = date('Y-m-d', $post['time_experience']);
            // Add rating to data 
            $select = Pi::model('post_rating', 'comment')->select()->where(array('post' => $post['id']));
            $rowset = Pi::model('post_rating', 'comment')->selectWith($select);
            foreach ($rowset as $row) {
                $data['rating-' . $row['rating_type']] = $row['rating'];
                $ratings[] = $row->toArray(); 
            }
            //
        }
     
        $options = array(
            'review' => $review
        );
        
        $form = Pi::api('api', 'comment')->getForm($data, $options);
        $form->setAttribute('action', $this->url('', array(
            'action'    => 'submit',
        )));

        $this->view()->assign('form', $form);
        $this->view()->assign('review', $review);
        $this->view()->assign('ratings', $ratings);
        $this->view()->setTemplate('comment-edit', '', 'front');
    }

    /**
     * Action for comment post submission
     */
    public function submitAction()
    {
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
            if ($redirect) {
                $redirect = urldecode($redirect);
            } elseif (!empty($result['data'])) {
                $redirect = $this->url('', array(
                    'action'    => 'index',
                    'id'        => $result['data']
                ));
            } else {
                $redirect = $this->url('', array('controller' => 'list'));
            }
            $this->jump($redirect, $result['message']);
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
        $id = 0;
        $isNew = false;
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $markup = $data['markup'];
            $ratings = isset($data['review']) && $data['review'] ? Pi::api('api', 'comment')->getRatings() : array();
            $form = new PostForm('comment-post', $markup, $ratings);
            $options = array('reply' => $data['reply'], 'review' => $data['review'], 'ratings' => $ratings);
            $form->setInputFilter(new PostFilter($options));
            
            $form->setData($data);
            if ($form->isValid()) {
                $values = $form->getData();
                if (empty($values['id'])) {
                    if (Pi::config('auto_approve', 'comment')) {
                        $values['active'] = 1;
                    }
                    $values['uid'] = Pi::service('user')->getId();
                    $values['ip'] = Pi::service('user')->getIp();
                    $isNew = true;
                }
                //vd($values);
                $id = Pi::api('api', 'comment')->addPost($values);
                if ($id) {
                    $status = 1;
                    $message = _a('Comment post saved successfully.');
                } else {
                    $status = 0;
                    $message = _a('Comment post not saved.');
                }
            } else {
                $status = -1;
                $message = _a('Invalid data, please check and re-submit.');
            }
        } else {
            $status = -2;
            $message = _a('Invalid submission.');
        }

        if (0 < $status && $id) {
            if ($isNew) {
                Pi::service('event')->trigger('post_submit', $id);
            } else {
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
        $id         = _get('id', 'int');
        $flag       = _get('flag');
        $return     = _get('return');
        $redirect   = _get('redirect');

        if (null === $flag) {
            $status = Pi::api('api', 'comment')->approve($id);
        } else {
            $status = Pi::api('api', 'comment')->approve($id, $flag);
        }
        $message = $status
            ? _a('Operation succeeded.') : _a('Operation failed.');

        if (0 < $status && $id) {
            if (null === $flag || $flag) {
                Pi::service('event')->trigger('post_enable', $id);
            } else {
                Pi::service('event')->trigger('post_disable', $id);
            }
        }

        if (!$return) {
            if ($redirect) {
                $redirect = urldecode($redirect);
            } else {
                $redirect = $this->url('', array(
                    'action'    => 'index',
                    'id'        => $id,
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
     * Batch enable or disable comment
     * 
     * @return viewModel 
     */
    public function batchApproveAction()
    {
        $id       = $this->params('id', '');
        $ids      = array_filter(explode(',', $id));
        $flag     = $this->params('flag', 0);
        $redirect = $this->params('redirect', '');

        $model  = $this->getModel('post');
        $model->update(array('active' => $flag), array('id' => $ids));

        if (null === $flag || $flag) {
            Pi::service('event')->trigger('post_enable', $ids);
        } else {
            Pi::service('event')->trigger('post_disable', $ids);
        }

        if ($redirect) {
            $redirect = urldecode($redirect);
            return $this->redirect()->toUrl($redirect);
        } else {
            // Go to list page
            return $this->redirect()->toRoute('', array(
                'action'     => 'index',
            ));
        }
    }

    /**
     * Delete a comment post
     *
     * @return array
     */
    public function deleteAction()
    {
        $id = _get('id', 'int');
        $return = _get('return');
        $redirect = _get('redirect');

        $post = Pi::api('api', 'comment')->getPost($id);
        if (!$post) {
            $status = -2;
            $message = __('Invalid post parameter.');
        } else {
            $status         = Pi::api('api', 'comment')->deletePost($id);
            $message        = $status
                ? __('Operation succeeded.') : __('Operation failed.');
        }
        if (0 < $status && $id) {
            Pi::service('event')->trigger('post_delete', $post['root']);
        }

        if (!$return) {
            if ($redirect) {
                $redirect = urldecode($redirect);
            } else {
                $redirect = $this->url('', array('controller' => 'list'));
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
     * Batch delete comments
     * 
     * @return viewModel 
     */
    public function batchDeleteAction()
    {
        $id       = $this->params('id', '');
        $ids      = array_filter(explode(',', $id));
        $redirect = $this->params('redirect', '');

        $model  = $this->getModel('post');
        $roots = array();
        $rowset = $model->select(array('id' => $ids));
        foreach ($rowset as $row) {
            $roots[] = $row['root'];
        }
        $model->delete(array('id' => $ids));

        Pi::service('event')->trigger('post_delete', $roots);

        if ($redirect) {
            $redirect = urldecode($redirect);
            return $this->redirect()->toUrl($redirect);
        } else {
            // Go to list page
            return $this->redirect()->toRoute('', array(
                'action'     => 'index',
            ));
        }
    }
}
