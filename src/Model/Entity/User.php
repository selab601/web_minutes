<?php
namespace App\Model\Entity;

use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property int $id
 * @property string $id_string
 * @property string $last_name
 * @property string $first_name
 * @property string $password
 * @property string $mail
 * @property bool $is_authorized
 * @property bool $is_deleted
 * @property \Cake\I18n\Time $created_at
 * @property \Cake\I18n\Time $updated_at
 *
 * @property \App\Model\Entity\Project[] $projects
 */
class User extends Entity
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

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array
     */
    protected $_hidden = [
        'password'
    ];

    protected function _setPassword($value) {
        $hasher = new DefaultPasswordHasher();
        return $hasher->hash($value);
    }

    protected function _getFullName()
    {
        return $this->_properties['last_name'] . '  ' .
            $this->_properties['first_name'];
    }
}
