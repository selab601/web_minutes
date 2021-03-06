<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * Minutes Controller
 *
 * @property \App\Model\Table\MinutesTable $Minutes
 */
class MinutesController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Delete');
        $this->loadComponent('SaveDiff');
    }

    public function isAuthorized($user)
    {
        // 議事録の追加は誰でも可能
        if ($this->request->action === 'add') {
            return true;
        }

        // 自分の参加しているプロジェクトの議事録であれば編集，閲覧が可能
        if (in_array($this->request->action, ['edit', 'view', 'createHtml', 'delete', 'examine', 'approve', 'ajaxUpdateItemOrder'])) {
            $minute_id = $this->request->params['pass'][0];
            $minute = $this->Minutes->get($minute_id);
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
     * View method
     *
     * @param string|null $id Minute id.
     * @return \Cake\Network\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $minute = $this->Minutes->get($id, [
            'contain' => ['Projects']
        ]);

        $users = $this->getUserParticipations($id, $minute->project_id);
        $user_array = [];
        foreach ($users as $user) {
            $u = [];
            $u['name'] = $user->last_name." ".$user->first_name;
            $u['participation'] = $user->participation;
            array_push($user_array, $u);
        }

        $project = $minute->project;
        $projects_users = TableRegistry::get('ProjectsUsers')
            ->find('all', ['contain'=>['Users', 'Roles']])
            ->where([
                'ProjectsUsers.project_id = '.$project->id,
                'ProjectsUsers.is_deleted = 0'
            ]);
        $items = $this->getItemsWithUserAndCategoryName($id);
        $this->set(compact('minute', 'items', 'project', 'projects_users', 'user_array'));
        $this->set('_serialize', ['minute']);
    }

    /**
     * Add method
     *
     * @return \Cake\Network\Response|void Redirects on successful add, renders view otherwise.
     */
    public function add($id = null)
    {
        $minute = $this->Minutes->newEntity();
        $projects_users = TableRegistry::get('ProjectsUsers')
            ->find('all', ['contain' => ['Users', 'Projects']])
            ->where(['ProjectsUsers.project_id = '.$id])
            ->all()->toArray();

        if ($this->request->is('post')) {
            $minute = $this->Minutes->patchEntity($minute, $this->request->data);
            $minute->project_id = $id;
            $minute->holded_at = $this->request->data["date"] . " " . $this->request->data["holded_at"];
            if (isset($this->request->data["ended_at"])) {
                $minute->ended_at = $this->request->data["date"] . " " . $this->request->data["ended_at"];
            }
            $now = new \DateTime();
            $minute->created_at = $now->format('Y-m-d H:i:s');
            $minute->updated_at = $now->format('Y-m-d H:i:s');

            $user_id = $this->request->session()->read('Auth.User.id');
            $user_name = $this->request->session()->read('Auth.User.last_name')
                . " " . $this->request->session()->read('Auth.User.first_name');
            $minute->created_by = $user_id;
            $minute->created_user_name = $user_name;

            if ($this->Minutes->save($minute)) {
                $data= $this->request->data;

                // 議事録へのユーザ参加の登録
                $projects_user_ids = $data["projects_users"]["_ids"];
                if (!empty($projects_user_ids)) {
                    foreach ($projects_user_ids as $projects_user_id => $attendance) {
                        if ($attendance == "×") { continue; }
                        $participations_registry = TableRegistry::get('Participations');
                        $participations = $participations_registry->newEntity();
                        $participations->projects_user_id = $projects_user_id;
                        $participations->minute_id = $minute->id;
                        $participations->state = $attendance;
                        if (!$participations_registry->save($participations)) {
                            $this->Flash->error('議事録の追加に失敗しました');
                        }
                    }
                }

                $this->Flash->success('議事録を追加しました');
                return $this->redirect(['controller' => 'projects', 'action' => 'view', $minute->project_id]);
            } else {
                $this->Flash->error('議事録の追加に失敗しました');
            }
        }

        $auth_projects_user = TableRegistry::get('ProjectsUsers')
            ->find('all')
            ->where([
                'ProjectsUsers.user_id = '.$this->request->session()->read('Auth.User.id'),
                'ProjectsUsers.project_id = '.$id
            ])
            ->first();
        $users_array = [];
        foreach ($projects_users as $projects_user) {
            $user = $projects_user->toArray()["user"];
            $users_array[$projects_user->id] =
                $user["last_name"] . " " . $user["first_name"];
        }
        $now = new \DateTime();

        $this->set(compact('minute', 'users_array', 'now', 'auth_projects_user'));
        $this->set('_serialize', ['minute']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Minute id.
     * @return \Cake\Network\Response|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $minute = $this->Minutes->get($id, [
            'contain' => ['Projects'],
        ]);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $minute = $this->Minutes->patchEntity($minute, $this->request->data);
            $date = $this->request->data["date"];
            $holded_at = $this->request->data["holded_at"];
            $minute->holded_at = $date . " " . $holded_at;
            if (isset($this->request->data["ended_at"])) {
                $ended_at = $this->request->data["ended_at"];
                $minute->ended_at = $date . " " . $ended_at;
            }
            if ($this->Minutes->save($minute)) {

                if (!empty($this->request->data["projects_users"]["_ids"])) {
                    $all_participations = TableRegistry::get('Participations')
                        ->find('all')
                        ->where(['Participations.minute_id = '.$id]);
                    if (!empty($all_participations->all())) {
                        foreach ($all_participations as $participation) {
                            if (!TableRegistry::get('Participations')->delete($participation)) {
                                throw new \Exception("Failed to delete participations");
                            }
                        }
                    }

                    foreach($this->request->data["projects_users"]["_ids"] as $projects_user_id => $state) {
                        if ($participation == "×") {continue;}
                        $participation = TableRegistry::get('Participations')->newEntity();
                        $participation->projects_user_id = $projects_user_id;
                        $participation->minute_id = $id;
                        $participation->state = $state;
                        if (!TableRegistry::get('Participations')->save($participation)) {
                            throw new \Exception("Failed to save participation");
                        }
                    }
                }

                $this->Flash->success('議事録を更新しました');
                return $this->redirect(['action' => 'view', $minute->id]);
            } else {
                $this->Flash->success('議事録の更新に失敗しました');
            }
        }

        // プロジェクトに参加しているユーザと，その中で出席済みのユーザを各々取得する
        $users = $this->getUserParticipations($minute->id, $minute->project_id);
        $users_array = [];
        $checked_users_array = [];
        foreach ($users as $user) {
            $users_array[$user->projects_user_id] = $user['last_name']." ".$user['first_name'];
            if ($user->participation != "×") {
                $checked_users_array[$user->projects_user_id] = $user->participation;
            }
        }
        $now = new \DateTime();
        $date = $minute->holded_at == NULL ? "" : $minute->holded_at->format('Y/m/d');
        $ended_at = $minute->ended_at == NULL ? "" : $minute->ended_at->format('H:i');
        $holded_at = $minute->holded_at == NULL ? "" : $minute->holded_at->format('H:i');

        $this->set(compact('minute', 'users_array', 'checked_users_array',
                'date', 'ended_at', 'holded_at'));
        $this->set('_serialize', ['minute']);
    }

    public function examine($id = null)
    {
        $minute = $this->Minutes->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $now = new \DateTime();
            $user_id = $this->request->session()->read('Auth.User.id');
            $user_name = $this->request->session()->read('Auth.User.last_name')
                . " " . $this->request->session()->read('Auth.User.first_name');

            $minute->examined_by = $user_id;
            $minute->examined_user_name = $user_name;
            $minute->is_examined = true;
            $minute->examined_at = $now->format('Y-m-d H:i:s');
            $minute->is_deletable = false;
            if (!$this->Minutes->save($minute)) {
                throw new \Exception('Failed to examine minute');
            }

            $this->Flash->success('「'.$minute->name.'」を審査済に更新しました');
        }

        return $this->redirect(['controller'=>'projects', 'action'=>'view', $minute->project_id]);
    }

    public function approve($id = null) {
        $minute = $this->Minutes->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $now = new \DateTime();
            $user_id = $this->request->session()->read('Auth.User.id');
            $user_name = $this->request->session()->read('Auth.User.last_name')
                . " " . $this->request->session()->read('Auth.User.first_name');
            $minute->approved_by = $user_id;
            $minute->approved_user_name = $user_name;
            $minute->is_approved = true;
            $minute->approved_at = $now->format('Y-m-d H:i:s');
            if (!$this->Minutes->save($minute)) {
                throw new \Exception('Failed to approve minute');
            }

            $this->Flash->success('「'.$minute->name.'」を承認済に更新しました');
        }

        return $this->redirect(['controller'=>'projects', 'action'=>'view', $minute->project_id]);
    }

    /**
     * Delete method
     *
     * @param string|null $id Minute id.
     * @return \Cake\Network\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $minute = TableRegistry::get('Minutes')->get($id, [
            'contain' => ['Participations', 'Items'],
        ]);
        $project_id = $minute->project_id;

        $this->Delete->Minute($id);

        $this->Flash->success('議事録を削除しました');

        return $this->redirect(['controller' => 'projects', 'action' => 'view', $project_id]);
    }

    public function createHtml($id) {
        $this->viewBuilder()->layout('create-html');

        $minute = $this->Minutes->get($id, [
            'contain' => ['Projects', ]
        ]);

        $users = $this->getUserParticipations($id, $minute->project_id);
        $user_array = [];
        foreach ($users as $user) {
            $u = [];
            $u['name'] = $user->last_name." ".$user->first_name;
            $u['participation'] = $user->participation;
            array_push($user_array, $u);
        }

        $items = $this->getItemsWithUserAndCategoryName($id);
        $this->set(compact('minute', 'items', 'user_array'));
        $this->set('_serialize', ['minute']);
    }

    public function ajaxUpdateItemOrder($id = null) {
        if ($this->request->is('post')) {
            $order_json_string = $this->request->data["order"];
            $minute_id = $this->request->data["minute_id"];
            $orders = json_decode($order_json_string, true);

            $items = $this->Minutes->Items
                ->find("all", ['order' => ['Items.order_in_minute' => 'ASC']])
                ->where(["Items.minute_id=".$minute_id]);
            foreach ($items as $item) {
                $item->order_in_minute = array_search($item->order_in_minute, $orders)+1;
                if (!$this->Minutes->Items->save($item)) {
                    echo "error";
                    exit();
                }
            }
        }
        echo "success";
        exit();
    }

    public static function saveParticipation($added_user_ids, $user_id, $minute_id) {
        foreach($added_user_ids as $user_id) {
            $participation = TableRegistry::get("Participations")->newEntity();
            $participation->minute_id = $minute_id;
            $participation->projects_user_id = $user_id;
            $participation->state = "◯";
            if (!TableRegistry::get("Participations")->save($participation)) {
                throw new \Exception('Failed to save participation entity');
            }
        }
    }

    public static function deleteParticipation($user_id, $minute_id) {
        $participation = TableRegistry::get("Participations")
            ->find('all')
            ->where(['participations.minute_id = '.$minute_id,
                    'participations.projects_user_id = '.$user_id])
            ->first();
        if (!TableRegistry::get('Participations')->delete($participation)) {
            throw new \Exception('Failed to delete participation entity');
        }
    }

    /**
     * 議事録の対象とする会議への出席情報を含んだユーザ情報を取得する
     *
     * @param $minute_id 議事録ID
     *
     * @return ユーザ情報を格納した連想配列．participation, name をもつ
     */
    private function getUserParticipations($minute_id, $project_id) {
        $users = TableRegistry::get('Users')
            ->find('all')
            ->innerJoin('projects_users', 'Users.id = projects_users.user_id')
            ->where('projects_users.project_id = '.$project_id)
            ->all()
            ->toArray();
        $projects_users = TableRegistry::get('ProjectsUsers')
            ->find('all', ['contain' => ['Users', 'Projects']])
            ->where(['ProjectsUsers.project_id = '.$project_id])
            ->all();
        foreach($projects_users as $key => $projects_user) {
            $participations = TableRegistry::get("Participations")
                ->find('all')
                ->where([
                    'Participations.minute_id = '.$minute_id,
                    'Participations.projects_user_id = '.$projects_user->id,
                ]);
            $users[$key]->projects_user_id = $projects_user->id;
            $users[$key]->participation = $participations->count()>0
                ? $participations->first()->state : "×";
        }
        return $users;
    }

    private function getItemsWithUserAndCategoryName($minute_id) {
        $items = [];
        $ordered_items = TableRegistry::get('Items')
            ->find('all', [
                'order' => ['Items.order_in_minute' => 'ASC']
            ])
            ->where(['Items.minute_id = '.$minute_id]);
        foreach ($ordered_items as $item) {
            $category = TableRegistry::get('ItemCategories')
                ->get($item->item_category_id, [
                    'contain' => ['ItemMetaCategories'],
                ]);

            $user_names = [];
            $responsibilities = TableRegistry::get('Responsibilities')
                ->find('all')
                ->where(['Responsibilities.item_id = '.$item->id]);
            foreach ($responsibilities as $responsibility) {
                $projects_users = TableRegistry::get('ProjectsUsers')->get($responsibility->projects_user_id);
                $user = TableRegistry::get('Users')->get($projects_users->user_id);
                array_push($user_names, $user->last_name);
            }

            $item->item_category_name = $category->name;
            $item->item_meta_category_name = $category->item_meta_category->name;
            $item->user_names = $user_names;

            array_push($items, $item);
        }
        return $items;
    }
}
