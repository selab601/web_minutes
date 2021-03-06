<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ProjectsUser Entity
 *
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property int $role_id
 * @property bool $is_deleted
 *
 * @property \App\Model\Entity\Project $project
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\Role $role
 * @property \App\Model\Entity\Participation[] $participations
 * @property \App\Model\Entity\Responsibility[] $responsibilities
 */
class ProjectsUser extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'id' => false
    ];
}
