<?php

/**
 * This is the model base class for the table "subject_matter".
 * DO NOT MODIFY THIS FILE! It is automatically generated by giix.
 * If any changes are necessary, you must set or override the required
 * property or method in class "SubjectMatter".
 *
 * Columns in table "subject_matter" available as properties of the model,
 * followed by relations of table "subject_matter" available as properties of the model.
 *
 * @property integer $id
 * @property string $name
 * @property integer $locked
 * @property string $created
 * @property string $modified
 *
 * @property Collection[] $collections
 * @property User[] $users
 */
abstract class BaseSubjectMatter_ extends GxActiveRecord {

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return 'subject_matter';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'Subject Matter|Subject Matters', $n);
	}

	public static function representingColumn() {
		return 'name';
	}

	public function rules() {
		return array(
			array('name, created, modified', 'required'),
			array('locked', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>64),
			array('locked', 'default', 'setOnEmpty' => true, 'value' => null),
			array('id, name, locked, created, modified', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'collections' => array(self::MANY_MANY, 'Collection', 'collection_to_subject_matter(subject_matter_id, collection_id)'),
			'users' => array(self::MANY_MANY, 'User', 'user_to_subject_matter(subject_matter_id, user_id)'),
		);
	}

	public function pivotModels() {
		return array(
			'collections' => 'CollectionToSubjectMatter',
			'users' => 'UserToSubjectMatter',
		);
	}

	public function attributeLabels() {
		return array(
			'id' => Yii::t('app', 'ID'),
			'name' => Yii::t('app', 'Name'),
			'locked' => Yii::t('app', 'Locked'),
			'created' => Yii::t('app', 'Created'),
			'modified' => Yii::t('app', 'Modified'),
			'collections' => null,
			'users' => null,
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('name', $this->name, true);
		$criteria->compare('locked', $this->locked);
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