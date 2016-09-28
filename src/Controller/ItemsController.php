<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * Items Controller
 *
 * @property \App\Model\Table\ItemsTable $Items
 */
class ItemsController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Delete');
    }

    public function isAuthorized($user)
    {
        // 案件の追加は誰でも可能
        if ($this->request->action === 'add') {
            return true;
        }

        // 自分の参加しているプロジェクトの議事録の案件であれば編集，閲覧が可能
        if (in_array($this->request->action, ['edit', 'view', 'delete'])) {
            $item_id = $this->request->params['pass'][0];
            $item = $this->Items->get($item_id);
            $minute = TableRegistry::get('Minutes')->get($item->minute_id);
            $user_id = $this->request->session()->read('Auth.User.id');
            $projects_users = TableRegistry::get("projects_users")
                ->find('all')
                ->where([
                    'projects_users.project_id = '.$minute->project_id,
                    'projects_users.user_id = '.$user_id,
                ])
                ->all();

            if (count($projects_users) != 0) {
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
        $this->paginate = [
            'contain' => ['Minutes', 'ItemCategories']
        ];
        $items = $this->paginate($this->Items);

        $this->set(compact('items'));
        $this->set('_serialize', ['items']);
    }

    /**
     * View method
     *
     * @param string|null $id Item id.
     * @return \Cake\Network\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $item = $this->Items->get($id, [
            'contain' => ['Minutes', 'ItemCategories', 'Responsibilities']
        ]);

        $this->set('item', $item);
        $this->set('_serialize', ['item']);
    }

    /**
     * Add method
     *
     * @return \Cake\Network\Response|void Redirects on successful add, renders view otherwise.
     */
    public function add($minute_id)
    {
        $item = $this->Items->newEntity();
        $minute = $this->Items->Minutes->get($minute_id);

        if ($this->request->is('post')) {
            $item = $this->Items->patchEntity($item, $this->request->data);
            $item->order_in_minute = $this->getMaxItemOrderNo($minute_id);
            $item->set('created_at', time());
            $item->set('updated_at', time());

            if ($this->Items->save($item)) {

                if (!empty($this->request->data["users"]["_ids"])) {
                    $responsibilities_registry = TableRegistry::get("Responsibilities");

                    foreach($this->request->data["users"]["_ids"] as $user_id) {
                        $responsibilities = $responsibilities_registry->newEntity();
                        $responsibilities->item_id = $item->id;
                        $responsibilities->projects_user_id = $user_id;

                        if (!$responsibilities_registry->save($responsibilities)) {
                            throw new \Exception('Failed to save responsibilities entity');
                        }
                    }
                }

                return $this->redirect(['controller' => 'minutes', 'action' => 'view', $minute->id]);
            } else {
                throw new \Exception('Failed to save item entity');
            }
        }

        $itemCategories = $this->Items->ItemCategories->find('list');

        $users = TableRegistry::get('Users')
            ->find('all')
            ->innerJoin('projects_users', 'Users.id = projects_users.user_id')
            ->where('projects_users.project_id = '.$minute->project_id)
            ->all()
            ->toArray();

        foreach($users as $key => $user) {
            $projects_user = TableRegistry::get("ProjectsUsers")
                ->find('all')
                ->where([
                    'ProjectsUsers.user_id = '.$user->id,
                    'ProjectsUsers.project_id = '. $minute->project_id
                ])
                ->first();
            $users[$key]->projects_user_id = $projects_user->id;
        }

        $this->set(compact('item', 'minute', 'itemCategories', 'users'));
        $this->set('_serialize', ['item']);
    }

    private function getMaxItemOrderNo($minute_id) {
        $max_no = 0;
        $items_num = $this->Items->find('all')
            ->where('Items.minute_id = '.$minute_id)
            ->count();
        if ($items_nu != 0){
            $max_no = $items_num;
        }
        return $max_no+1;
    }

    /**
     * Edit method
     *
     * @param string|null $id Item id.
     * @return \Cake\Network\Response|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $item = $this->Items->get($id);
        $minute = $this->Items->Minutes->get($item->minute_id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $item = $this->Items->patchEntity($item, $this->request->data);
            if ($this->Items->save($item)) {
                // 編集前の担当者
                $users_selected_old = TableRegistry::get("Responsibilities")
                    ->find('all', ['fields'=>'Responsibilities.projects_user_id'])
                    ->where(['Responsibilities.item_id = '.$item->id])
                    ->all()->toArray();
                $old_selected_user_ids = [];
                foreach ($users_selected_old as $user) {
                    array_push($old_selected_user_ids, (string)$user->projects_user_id);
                }
                if (empty($old_selected_user_ids)){ $old_selected_user_ids = []; }

                // 編集後の担当者
                $new_selected_user_ids = $this->request->data["projects_users"]["_ids"];
                if (empty($new_selected_user_ids)){ $new_selected_user_ids = []; }

                // 前2つの担当者の差分を比較し，追加/削除を行う
                $responsibilities_registry = TableRegistry::get("Responsibilities");
                $deleted_user_ids = array_diff($old_selected_user_ids, $new_selected_user_ids);
                $added_user_ids = array_diff($new_selected_user_ids, $old_selected_user_ids);

                if (!empty($deleted_user_ids)) {
                    foreach($deleted_user_ids as $user_id) {
                        $responsibility = $responsibilities_registry
                            ->find('all')
                            ->where(['responsibilities.item_id = '.$item->id,
                                     'responsibilities.projects_user_id = '.$user_id])
                            ->first();
                        if (!$responsibilities_registry->delete($responsibility)) {
                            throw new \Exception('Failed to delete responsibility entity');
                        }
                    }
                }

                if (!empty($added_user_ids)) {
                    foreach($added_user_ids as $user_id) {
                        $responsibility = $responsibilities_registry->newEntity();
                        $responsibility->item_id = $item->id;
                        $responsibility->projects_user_id = $user_id;
                        if (!$responsibilities_registry->save($responsibility)) {
                            throw new \Exception('Failed to save responsibility entity');
                        }
                    }
                }

                return $this->redirect(['controller' => 'minutes', 'action' => 'view', $minute->id]);
            } else {
                throw new \Exception('Failed to save item entity');
            }
        }
        $minute = $this->Items->Minutes->get($item->minute_id);
        $itemCategories = $this->Items->ItemCategories->find('list', ['limit' => 200]);

        $users = TableRegistry::get('Users')
            ->find('all')
            ->innerJoin('projects_users', 'Users.id = projects_users.user_id')
            ->where('projects_users.project_id = '.$minute->project_id)
            ->all()
            ->toArray();

        foreach($users as $key => $user) {
            $projects_user = TableRegistry::get("ProjectsUsers")
                ->find('all')
                ->where([
                    'ProjectsUsers.user_id = '.$user->id,
                    'ProjectsUsers.project_id = '. $minute->project_id
                ])
                ->first();

            $responsibility = TableRegistry::get('Responsibilities')
                ->find('all')
                ->where(['responsibilities.item_id = '.$item->id])
                ->innerJoin('projects_users', 'projects_users.id = Responsibilities.projects_user_id')
                ->where(['projects_users.user_id = '.$user->id])
                ->all()
                ->toArray();

            $users[$key]->projects_user_id = $projects_user->id;
            $users[$key]->has_responsibility = count($responsibility)>0 ? true : false;
        }

        $this->set(compact('item', 'itemCategories', 'users'));
        $this->set('_serialize', ['item']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Item id.
     * @return \Cake\Network\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $item = TableRegistry::get("Items")->get($id, ['id']);
        $minute_id = $item->minute_id;

        $this->Delete->Item($id);

        return $this->redirect(['controller' => 'minutes', 'action' => 'view', $minute_id]);
    }
}
