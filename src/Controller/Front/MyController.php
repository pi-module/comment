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
use Pi\Paginator\Paginator;
use Laminas\Db\Sql\Expression;

/**
 * Comment list controller
 *
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
class MyController extends ActionController
{
    /**
     * All comment posts
     */
    public function indexAction()
    {
        $page   = _get('page', 'int') ?: 1;
        $where = array('active' => 1);
        
        $uid = Pi::user()->getId();
        $result = Pi::api('api', 'comment')->getComments($page, $uid);

        $this->view()->assign('comment', array(
            'title'     => $this->config('head_title'),
            'count'     => $result['count'],
            'posts'     => $result['posts'],
            'paginator' => $result['paginator'],
        ));
        
        $this->view()->setTemplate('comment-list-my');
        $this->view()->assign('my', true);
        $this->view()->headTitle($this->config('head_title'));
        $this->view()->headMeta($this->config('head_title'), 'twitter:title', 'name');
        $this->view()->headMeta($this->config('head_title'), 'og:title', 'property');
        $this->view()->headDescription($this->config('description'), 'set');
        $this->view()->headMeta($this->config('description'), 'twitter:description');
        $this->view()->headMeta($this->config('description'), 'og:description', 'property');
        $this->view()->headKeywords($this->config('keywords'), 'set');
    }
}
