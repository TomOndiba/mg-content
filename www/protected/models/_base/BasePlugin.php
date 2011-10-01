<?php

/**
 * This is the model base class for the table "plugin".
 * DO NOT MODIFY THIS FILE! It is automatically generated by giix.
 * If any changes are necessary, you must set or override the required
 * property or method in class "Plugin".
 *
 * Columns in table "plugin" available as properties of the model,
 * followed by relations of table "plugin" available as properties of the model.
 *
 * @property integer $id
 * @property string $type
 * @property integer $active
 * @property string $unique_id
 * @property string $created
 * @property string $modified
 *
 * @property Game[] $games
 */
abstract class BasePlugin extends GxActiveRecord {

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return 'plugin';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'Plugin|Plugins', $n);
	}

	public static function representingColumn() {
		return 'unique_id';
	}

	public function rules() {
		return array(
			array('type, active, unique_id, created, modified', 'required'),
			array('active', 'numerical', 'integerOnly'=>true),
			array('type', 'length', 'max'=>20),
			array('unique_id', 'length', 'max'=>254),
			array('id, type, active, unique_id, created, modified', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'games' => array(self::MANY_MANY, 'Game', 'game_to_plugin(plugin_id, game_id)'),
		);
	}

	public function pivotModels() {
		return array(
			'games' => 'GameToPlugin',
		);
	}

	public function attributeLabels() {
		return array(
			'id' => Yii::t('app', 'ID'),
			'type' => Yii::t('app', 'Type'),
			'active' => Yii::t('app', 'Active'),
			'unique_id' => Yii::t('app', 'Unique'),
			'created' => Yii::t('app', 'Created'),
			'modified' => Yii::t('app', 'Modified'),
			'games' => null,
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('type', $this->type, true);
		$criteria->compare('active', $this->active);
		$criteria->compare('unique_id', $this->unique_id, true);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('modified', $this->modified, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination'=>array(
        'pageSize'=>Yii::app()->fbvStorage->get("settings.pagination_size"),
      ),
		));
	}
}