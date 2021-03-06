<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ItemCategories Model
 *
 * @property \Cake\ORM\Association\BelongsTo $ItemMetaCategories
 * @property \Cake\ORM\Association\HasMany $Items
 *
 * @method \App\Model\Entity\ItemCategory get($primaryKey, $options = [])
 * @method \App\Model\Entity\ItemCategory newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\ItemCategory[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ItemCategory|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ItemCategory patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ItemCategory[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\ItemCategory findOrCreate($search, callable $callback = null)
 */
class ItemCategoriesTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('item_categories');
        $this->displayField('name');
        $this->primaryKey('id');

        $this->belongsTo('ItemMetaCategories', [
            'foreignKey' => 'item_meta_category_id',
            'joinType' => 'INNER'
        ]);
        $this->hasMany('Items', [
            'foreignKey' => 'item_category_id'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id')
            ->allowEmpty('id', 'create');

        $validator
            ->requirePresence('name', 'create')
            ->notEmpty('name');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['item_meta_category_id'], 'ItemMetaCategories'));

        return $rules;
    }
}
