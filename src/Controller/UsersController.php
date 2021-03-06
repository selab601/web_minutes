<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{

    public function initialize()
    {
        parent::initialize();
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        // ログインしていないユーザ含め，全ユーザがアクセス可能なページ
        // WARNING: login を含めると動作がおかしくなるため含めない
        $this->Auth->allow(['logout', 'signup']);
    }

    public function isAuthorized($user)
    {
        if ($this->request->action == 'projectsView') {
            return true;
        }

        // 権限がなくとも，自分の情報であれば編集，閲覧可能
        if (in_array($this->request->action, ['edit', 'view'])) {
            $userId = $this->request->params['pass'][0];
            if ($userId == $user['id']) {
                return true;
            }
        }

        return parent::isAuthorized($user);
    }

    /**
     * Index method
     *
     * @return \Cake\Network\Response|null
     */
    public function index()
    {
        $users = $this->paginate($this->Users);

        $this->set(compact('users'));
        $this->set('_serialize', ['users']);
    }

    /**
     * View method
     *
     * @param string|null $id User id.
     * @return \Cake\Network\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $id = $this->request->session()->read('Auth.User.id');

        $user = $this->Users->get($id);

        $this->set('user', $user);
        $this->set('_serialize', ['user']);
    }

    public function projectsView($id = null) {
        $id = $this->request->session()->read('Auth.User.id');

        $user = $this->Users->get($id, [
            'contain' => ['Projects']
        ]);

        $this->set('user', $user);
        $this->set('_serialize', ['user']);
    }

    /**
     * signup method
     *
     * @return \Cake\Network\Response|void Redirects on successful add, renders view otherwise.
     */
    public function signup()
    {
        $user = $this->Users->newEntity();
        if ($this->request->is('post')) {
            $now = new \DateTime();
            $user = $this->Users->patchEntity($user, $this->request->data);
            $user->created_at = $now->format('Y/m/d H:i:s');
            $user->updated_at = $now->format('Y/m/d H:i:s');
            // 新規登録時に管理者権限は付与できないようにする
            $user->is_authorized = 0;
            if ($this->Users->save($user)) {
                $this->Flash->success('ユーザ登録に成功しました');
                return $this->redirect(['action' => 'login']);
            } else {
                $this->Flash->error('ユーザ登録に失敗しました');
            }
        }
        $projects = $this->Users->Projects->find('list', ['limit' => 200]);
        $this->set(compact('user', 'projects'));
        $this->set('_serialize', ['user']);
    }

    /**
     * Edit method
     *
     * @param string|null $id User id.
     * @return \Cake\Network\Response|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $user = $this->Users->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            if (empty($this->request->data["password"])) {
                unset($this->request->data["password"]);
            }
            // 管理者権限を持つ人のみ管理者権限を追加できる
            $is_authorized = $this->request->session()->read('Auth.User.is_authorized');
            if ($is_authorized == 0) {
                $this->request->data["is_authorized"] = 0;
            }

            $user = $this->Users->patchEntity($user, $this->request->data);

            if ($this->Users->save($user)) {
                $this->Flash->success(__('ユーザ情報を更新しました'));

                return $this->redirect(['action' => 'view', $user->id]);
            } else {
                $this->Flash->error(__('ユーザ情報の更新に失敗しました'));
            }
        }

        $this->set(compact('user'));
        $this->set('_serialize', ['user']);
    }

    /**
     * Delete method
     *
     * @param string|null $id User id.
     * @return \Cake\Network\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('ユーザを駆除しました'));
        } else {
            $this->Flash->error(__('ユーザの削除に失敗しました'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function login()
    {
        if ($this->request->is('post')) {
            $user = $this->Auth->identify();
            if ($user) {
                $this->Auth->setUser($user);
                return $this->redirect($this->Auth->redirectUrl());
            }
            $this->Flash->error('IDまたはパスワードが不正です');
        }
        $user = $this->Users->newEntity();
        $this->set(compact('user'));
    }

    public function logout()
    {
        return $this->redirect($this->Auth->logout());
    }
}
